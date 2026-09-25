<?php

declare(strict_types=1);

namespace App\Support\Scramble;

use App\Query\Attributes\QueryParameters;
use App\Query\Definitions\BaseQueryDefinition;
use App\Query\Support\QueryDefinitionParser;
use Dedoc\Scramble\Extensions\OperationExtension;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Parameter;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\BooleanType;
use Dedoc\Scramble\Support\Generator\Types\IntegerType;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\RouteInfo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use ReflectionClass;

final class QueryParametersExtension extends OperationExtension
{
    public function handle(Operation $operation, RouteInfo $routeInfo): void
    {
        $definitionClass = $this->resolveQueryDefinitionClass($routeInfo);

        if (! $definitionClass || ! class_exists($definitionClass)) {
            return;
        }

        $parsed = QueryDefinitionParser::parse($definitionClass);
        $parameters = [];

        // Pagination
        if (! ($parsed['disable_paginate'] ?? false)) {
            $parameters[] = Parameter::make('page', 'query')
                ->setSchema(Schema::fromType(new IntegerType()))
                ->description('Page number.')
                ->example(1);

            $parameters[] = Parameter::make('per_page', 'query')
                ->setSchema(Schema::fromType(new IntegerType()))
                ->description('Number of items per page. Maximum 100.')
                ->example($parsed['paginate_perpage'] ?? 15);
        }

        // Sorting
        if (! ($parsed['disable_sort'] ?? false) && $parsed['sorts']->isNotEmpty()) {
            $sortValues = $parsed['sorts']->values()->all();
            $parameters[] = Parameter::make('sort', 'query')
                ->setSchema(Schema::fromType(new StringType()))
                ->description(sprintf('Comma-separated sorts. Prefix with `-` for descending order. Allowed values: `%s`.', implode('`, `', $sortValues)))
                ->example($parsed['default_sort'] ?? '-created_at');
        }

        // Includes
        if (! ($parsed['disable_include'] ?? false) && $parsed['includes']->isNotEmpty()) {
            $includeValues = $parsed['includes']->values()->all();
            $parameters[] = Parameter::make('include', 'query')
                ->setSchema(Schema::fromType(new StringType()))
                ->description(sprintf('Comma-separated relationships to eager load. Allowed values: `%s`.', implode('`, `', $includeValues)))
                ->example($includeValues[0] ?? null);
        }

        // Filters
        if (! ($parsed['disable_filter'] ?? false)) {
            if (! empty($parsed['searchable'])) {
                $parameters[] = Parameter::make('filter[search]', 'query')
                    ->setSchema(Schema::fromType(new StringType()))
                    ->description(sprintf('Global search across columns: `%s`.', implode('`, `', $parsed['searchable'])));
            }

            foreach ($parsed['filters'] as $filter) {
                $parameters[] = Parameter::make("filter[{$filter->name}]", 'query')
                    ->setSchema(Schema::fromType(new StringType()))
                    ->description("Filter by `{$filter->name}`.");
            }

            /** @var class-string<\Illuminate\Database\Eloquent\Model> $modelClass */
            $modelClass = is_subclass_of($definitionClass, BaseQueryDefinition::class)
                ? $definitionClass::model()
                : $definitionClass;

            if (class_exists($modelClass) && in_array(SoftDeletes::class, class_uses_recursive($modelClass), true)) {
                $parameters[] = Parameter::make('filter[trashed]', 'query')
                    ->setSchema(Schema::fromType(new StringType()))
                    ->description('Filter by trashed status: `with` (include deleted), `only` (only deleted), or `without` (active only).')
                    ->example('without');
            }
        }

        // Fields (Sparse fieldsets)
        if ($parsed['fields']->isNotEmpty()) {
            /** @var class-string<\Illuminate\Database\Eloquent\Model> $modelClass */
            $modelClass = is_subclass_of($definitionClass, BaseQueryDefinition::class)
                ? $definitionClass::model()
                : $definitionClass;

            $modelName = class_basename($modelClass);
            $primaryKey = Str::snake(Str::plural($modelName));

            $groups = [];
            foreach ($parsed['fields'] as $field) {
                if (Str::contains($field, '.')) {
                    $parts = explode('.', $field);
                    $groups[$parts[0]][] = $parts[1];
                } else {
                    $groups[$primaryKey][] = $field;
                }
            }

            foreach ($groups as $table => $columns) {
                $parameters[] = Parameter::make("fields[{$table}]", 'query')
                    ->setSchema(Schema::fromType(new StringType()))
                    ->description(sprintf('Select fields to fetch for `%s`. Allowed values: `%s`.', $table, implode('`, `', $columns)));
            }
        }

        // Export
        if (! ($parsed['disable_export'] ?? false)) {
            $parameters[] = Parameter::make('export', 'query')
                ->setSchema(Schema::fromType(new BooleanType()))
                ->description('Export query results directly as an Excel spreadsheet (.xlsx).')
                ->example(false);
        }

        $operation->addParameters($parameters);

        // 200 Response Schema & Example
        $this->attachResponseSchema($operation, $routeInfo, $parsed, $definitionClass);
    }

    /**
     * @param  array<string, mixed>  $parsed
     * @param  class-string  $definitionClass
     */
    private function attachResponseSchema(Operation $operation, RouteInfo $routeInfo, array $parsed, string $definitionClass): void
    {
        $resourceClass = $this->resolveResourceClass($routeInfo);

        if (! $resourceClass || ! class_exists($resourceClass)) {
            return;
        }

        $resourceType = new \Dedoc\Scramble\Support\Type\ObjectType($resourceClass);
        $resourceSchema = $this->openApiTransformer->transform($resourceType);

        $dataSchema = (new \Dedoc\Scramble\Support\Generator\Types\ArrayType)
            ->setItems($resourceSchema);

        $isPaginated = ! ($parsed['disable_paginate'] ?? false);
        $message = $this->resolveSuccessMessage($routeInfo, $definitionClass);

        $responseProperties = [
            'success' => (new BooleanType)->example(true),
            'message' => (new StringType)->example($message),
            'data' => $dataSchema,
        ];

        if ($isPaginated) {
            $metaObj = new \Dedoc\Scramble\Support\Generator\Types\ObjectType;
            $metaObj->properties = [
                'current_page' => (new IntegerType)->example(1),
                'from' => (new IntegerType)->example(1),
                'last_page' => (new IntegerType)->example(1),
                'per_page' => (new IntegerType)->example($parsed['paginate_perpage'] ?? 15),
                'to' => (new IntegerType)->example(15),
                'total' => (new IntegerType)->example(1),
            ];
            $metaObj->setRequired(['current_page', 'from', 'last_page', 'per_page', 'to', 'total']);

            $linksObj = new \Dedoc\Scramble\Support\Generator\Types\ObjectType;
            $linksObj->properties = [
                'first' => (new StringType)->nullable(true)->example('http://api-starter.test/api/v1/resources?page=1'),
                'last' => (new StringType)->nullable(true)->example('http://api-starter.test/api/v1/resources?page=1'),
                'prev' => (new StringType)->nullable(true)->example(null),
                'next' => (new StringType)->nullable(true)->example(null),
            ];
            $linksObj->setRequired(['first', 'last', 'prev', 'next']);

            $responseProperties['meta'] = $metaObj;
            $responseProperties['links'] = $linksObj;
        } else {
            $responseProperties['meta'] = new \Dedoc\Scramble\Support\Generator\Types\NullType;
        }

        $schemaObj = new \Dedoc\Scramble\Support\Generator\Types\ObjectType;
        $schemaObj->properties = $responseProperties;
        $schemaObj->setRequired(array_keys($responseProperties));

        $response200 = \Dedoc\Scramble\Support\Generator\Response::make(200)
            ->setDescription('Successful list retrieval or spreadsheet file export')
            ->setContent('application/json', Schema::fromType($schemaObj));

        if (! ($parsed['disable_export'] ?? false)) {
            $binarySchema = (new StringType)
                ->format('binary');

            $response200->setContent(
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                Schema::fromType($binarySchema)
            );
        }

        // Replace or add 200 response
        $operation->responses = array_values(array_filter(
            $operation->responses ?? [],
            fn ($r) => ($r instanceof \Dedoc\Scramble\Support\Generator\Response ? $r->code : null) !== 200
        ));

        $operation->addResponse($response200);
    }

    private function resolveResourceClass(RouteInfo $routeInfo): ?string
    {
        $className = $routeInfo->className();
        if (! $className) {
            return null;
        }

        $reflection = new ReflectionClass($className);
        if ($reflection->hasProperty('resource')) {
            $val = $reflection->getProperty('resource')->getDefaultValue();
            if (is_string($val) && class_exists($val)) {
                return $val;
            }
        }

        $baseClass = class_basename($className);
        $entityName = str_replace('Controller', '', $baseClass);
        $candidate = "App\\Http\\Resources\\{$entityName}Resource";

        if (class_exists($candidate)) {
            return $candidate;
        }

        return null;
    }

    private function resolveSuccessMessage(RouteInfo $routeInfo, string $definitionClass): string
    {
        $className = $routeInfo->className();
        if ($className) {
            $reflection = new ReflectionClass($className);
            if ($reflection->hasProperty('indexMessage')) {
                $val = $reflection->getProperty('indexMessage')->getDefaultValue();
                if (is_string($val) && $val !== '') {
                    return $val;
                }
            }

            $baseClass = class_basename($className);
            $entityName = str_replace('Controller', '', $baseClass);
            $plural = Str::plural(Str::headline($entityName));

            return "{$plural} retrieved successfully.";
        }

        return 'Data retrieved successfully.';
    }

    /**
     * @return class-string|null
     */
    private function resolveQueryDefinitionClass(RouteInfo $routeInfo): ?string
    {
        if (! $routeInfo->isClassBased()) {
            return null;
        }

        $reflectionMethod = $routeInfo->reflectionMethod();
        if (! $reflectionMethod) {
            return null;
        }

        // 1. Check QueryParameters attribute on method or class
        $attrs = $reflectionMethod->getAttributes(QueryParameters::class);
        if (! empty($attrs)) {
            return $attrs[0]->newInstance()->definition;
        }

        $className = $routeInfo->className();
        if ($className) {
            $classAttrs = (new ReflectionClass($className))->getAttributes(QueryParameters::class);
            if (! empty($classAttrs)) {
                return $classAttrs[0]->newInstance()->definition;
            }
        }

        // 2. Check conventional mapping on index method
        if ($routeInfo->methodName() === 'index' && $className) {
            $baseClass = class_basename($className);
            $entityName = str_replace('Controller', '', $baseClass);
            $candidate = "App\\Query\\Definitions\\{$entityName}QueryDefinition";

            if (class_exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}

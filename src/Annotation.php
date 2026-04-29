<?php

declare(strict_types=1);

namespace Kingbes\Attribute;

/**
 * 注解 class
 */
#[\Attribute(\Attribute::TARGET_ALL | \Attribute::IS_REPEATABLE)]
class Annotation
{

    /**
     * 数据
     * @var array
     */
    protected array $data = [];

    /**
     * 注解 function
     *
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * 获取
     *
     * @return array
     */
    public function get(): array
    {
        return $this->data;
    }
}

<?php
/**
 * Created by Marcin.
 * Date: 30.03.2019
 * Time: 20:54
 */

namespace Mrcnpdlk\Lib;


use JsonMapper;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use function get_class;
use function sprintf;

/**
 * Class Mapper
 *
 * @package Mrcnpdlk\Lib
 */
class Mapper
{
    /**
     * @var \JsonMapper
     */
    private $jsonMapper;

    /**
     * Mapper constructor.
     *
     * @param \JsonMapper|null              $jsonMapper
     * @param \Psr\Log\LoggerInterface|null $logger
     */
    public function __construct(?JsonMapper $jsonMapper, LoggerInterface $logger = null)
    {
        $logger = $logger ?? new NullLogger();

        if (null === $jsonMapper) {
            $jsonMapper                            = new JsonMapper();
            $jsonMapper->bStrictNullTypes          = true;
            $jsonMapper->bExceptionOnMissingData   = true;
            $jsonMapper->bEnforceMapType           = false;
            $jsonMapper->bStrictObjectTypeChecking = false;
            /** @noinspection PhpParamsInspection */
            $jsonMapper->setLogger($logger);

            $jsonMapper->undefinedPropertyHandler = static function (
                object $object,
                string $propName,
                /* @noinspection PhpUnusedParameterInspection */
                $jsonValue
            ) use ($logger) {
                $logger
                    ->warning(sprintf('Undefined property "%s" in object [%s]', $propName, get_class($object)));
            };
        }
        $this->jsonMapper = $jsonMapper;
    }


    /**
     * @param string       $class
     * @param array|object ...$model
     *
     * @return object
     * @throws \Mrcnpdlk\Lib\ModelMapException
     */
    public function jsonMap(string $class, ...$model)
    {
        if (!class_exists($class)) {
            throw new ModelMapException(sprintf('Class %s not exists', $class));
        }
        try {
            array_walk($model, static function (&$item) {
                $item = (array)$item;
            });

            /** @noinspection PhpParamsInspection */
            return $this->jsonMapper->map(array_merge(...$model), new $class());
        } catch (\Exception $e) {
            throw new ModelMapException($e->getMessage());
        }
    }

    /**
     * @param string $class
     * @param array  $array
     *
     * @return object[]
     * @throws \Mrcnpdlk\Lib\ModelMapException
     */
    public function jsonMapArray(string $class, array $array): array
    {
        if (!class_exists($class)) {
            throw new ModelMapException(sprintf('Class %s not exists', $class));
        }
        try {
            return $this->jsonMapper->mapArray($array, [], $class);
        } catch (\Exception $e) {
            throw new ModelMapException($e->getMessage());
        }
    }
}

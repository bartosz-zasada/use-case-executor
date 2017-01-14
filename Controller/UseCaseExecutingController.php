<?php

namespace Bamiz\UseCaseBundle\Controller;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class UseCaseExecutingController implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var string
     */
    private $actorName;

    /**
     * @param string $name
     *
     * @return $this
     */
    public function __get($name)
    {
        $this->actorName = $name;
        return $this;
    }

    /**
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        $useCaseName = $this->camelCaseToSnakeCase($name);
        $useCaseExecutor = $this->container->get('bamiz_use_case.executor');
        if ($this->actorName) {
            $useCaseExecutor = $useCaseExecutor->asActor($this->actorName);
        }

        if (isset($arguments[0])) {
            return $useCaseExecutor->execute($useCaseName, $arguments[0]);
        } else {
            return $useCaseExecutor->execute($useCaseName);
        }
    }

    /**
     * @param $name
     *
     * @return string
     */
    private function camelCaseToSnakeCase($name)
    {
        return strtolower(preg_replace('/([A-Z])/', '_$1', $name));
    }
}

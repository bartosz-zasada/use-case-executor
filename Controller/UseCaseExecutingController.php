<?php

namespace Bamiz\UseCaseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class UseCaseExecutingController extends Controller
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
        $useCaseExecutor = $this->get('bamiz_use_case.executor');
        if ($this->actorName) {
            $useCaseExecutor = $useCaseExecutor->asActor($this->actorName);
        }

        $executorArguments = array_merge([$useCaseName], $arguments);

        return call_user_func_array([$useCaseExecutor, 'execute'], $executorArguments);
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

<?php

namespace Bamiz\UseCaseExecutor\Processor\Input;

use Symfony\Component\OptionsResolver\OptionsResolver;

class ArrayInputProcessor implements InputProcessorInterface
{
    /**
     * Initializes the provided Use Case Request by copying the data from the input array.
     * Available options:
     * - map - optional. Allows to specify custom mapping from array keys to the fields in the use case request. Use an
     *   associative array with input array keys as keys and Use Case Request field names as values.
     *
     * @param object $request The Use Case Request object to be initialized.
     * @param array  $input   An array containing input data.
     * @param array  $options An array of configuration options.
     *
     * @return object the Use Case Request object is returned for testability purposes.
     */
    public function initializeRequest($request, $input, array $options = [])
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults(['map' => []]);
        $options = $resolver->resolve($options);

        $map = array_flip($options['map']);
        foreach ($request as $field => &$value) {
            if (isset($map[$field])) {
                $field = $map[$field];
            }
            if (isset($input[$field])) {
                $value = $input[$field];
            }
        }

        return $request;
    }
}

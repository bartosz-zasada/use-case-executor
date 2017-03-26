<?php

namespace spec\Bamiz\UseCaseExecutor\Processor\Input;

use Bamiz\UseCaseExecutor\Processor\Input\ArrayInputProcessor;
use Bamiz\UseCaseExecutor\Processor\Input\InputProcessorInterface;
use PhpSpec\ObjectBehavior;

class ArrayInputProcessorSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(ArrayInputProcessor::class);
    }

    public function it_is_an_input_processor()
    {
        $this->shouldHaveType(InputProcessorInterface::class);
    }

    public function it_throws_an_exception_if_an_unrecognized_option_is_used()
    {
        $options = ['what is this' => 'crazy thing'];
        $this->shouldThrow(\InvalidArgumentException::class)->duringInitializeRequest(new MyRequest(), [], $options);
    }

    public function it_copies_the_data_from_the_array_to_the_request_object()
    {
        $data = [
            'stringField' => 'some string',
            'numberField' => 216,
            'booleanField' => true,
            'arrayField' => [1, 2, 3, 4],
            'noSuchField' => true
        ];
        /** @var MyRequest $request */
        $request = $this->initializeRequest(new MyRequest(), $data);

        $request->stringField->shouldBe($data['stringField']);
        $request->numberField->shouldBe($data['numberField']);
        $request->booleanField->shouldBe($data['booleanField']);
        $request->arrayField->shouldBe($data['arrayField']);
        $request->omittedField->shouldBe(null);
        $request->omittedFieldWithDefaultValue->shouldBe('asdf');
    }

    public function it_maps_fields_from_array_to_object_using_custom_mappings()
    {
        $data = [
            'q' => 'search',
            'pi' => 3.1415,
            'flag' => false,
            'data' => ['x', 'y', 'z']
        ];
        $options = [
            'map' => [
                'q' => 'stringField',
                'pi' => 'numberField',
                'flag' => 'booleanField',
                'data' => 'arrayField'
            ]
        ];
        /** @var MyRequest $request */
        $request = $this->initializeRequest(new MyRequest(), $data, $options);

        $request->stringField->shouldBe($data['q']);
        $request->numberField->shouldBe($data['pi']);
        $request->booleanField->shouldBe($data['flag']);
        $request->arrayField->shouldBe($data['data']);
        $request->omittedField->shouldBe(null);
        $request->omittedFieldWithDefaultValue->shouldBe('asdf');
    }
}

class MyRequest
{
    public $stringField;
    public $numberField;
    public $booleanField;
    public $arrayField;
    public $omittedField;
    public $omittedFieldWithDefaultValue = 'asdf';
}

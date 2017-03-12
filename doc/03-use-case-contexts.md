# Use Case Contexts
In [the previous chapter](02-use-cases-in-symfony.md) we have created an **Input Processor** and a **Response Processor**, 
and used them to provide a **Context** for the Use Case to be executed in. It has been noted that the Use Case Layer must 
stay separated from the Application Layer in the way that the Use Cases, or any business logic, does not depend on the way 
that Input or Output is delivered. This means that we can replace the Use Case Context at any time, and the behavior of 
the application will stay intact. The most noteworthy benefit is the ability to test the application with functional tests. 
These tests would focus on verifying the behavior without resorting to performing fragile assertions that base on the UI, 
and it will be only necessary to change them when the business rules change, and nothing else.

In the example from the previous chapter, we used annotations to define the Use Case Context. While being very easy
to use, annotations have a nasty side effect of coupling our business logic layer (Use Cases) with the application
layer, as the details of the delivery mechanism are now present in the same file as the business logic.
Fortunately, the Use Case Bundle provides several other means of configuring Contexts, which should be used instead
of annotations if you want to keep everything truly separated.

## Default Context
You can configure the default Context in config.yml:

```
# app/config/config.yml

bamiz_use_case:
    contexts:
        default:
            input:    my_input_processor
            response: my_response_processor
    
```

If you want to specify additional options with your default Context, the name of the Processor must be provided 
as a key, and the options must be provided as key-value pairs under that key:

```
# app/config/config.yml

bamiz_use_case:
    contexts:
        default:
            input:    
                my_input_processor:
                    foo:  bar
                    bar:  baz
            response: 
                my_response_processor:
                    format:   json
                    encoding: utf-8
    
```

These settings will be used as a fallback in case the Input Processor or the Response Processor have 
not been specified in any other way.

If you have not specified the defaults in config.yml, the default Input Processor is `array` and the default 
Response Processor is `identity`.

You can also specify multiple Input and Response Processors. To do that, simply add another entry like described above.
If your Processor does not require any options, just put tilde next to the key:


## Anonymous Contexts

A Context can be defined ad hoc and passed as the third argument to the Executor's `execute()` method:

```
<?php
$executor->execute('my_use_case', $input, ['input' => 'http', 'response' => 'json']);
$executor->execute('my_use_case', $input, [
    'input' => [
        'http',
        'form' => ['name' => 'AppBundle\Form\MyForm', 'data_field' => 'myFormData']
    ],
    'response' => [
        'twig' => ['template' => 'MyBundle:default:index.html.twig']
    ]
]);
```

If Input or Response Processor is not specified, the default one is used.


```
# app/config/config.yml

bamiz_use_case:
    contexts:
        default:
            input:    
                my_input_processor:
                    foo:  bar
                    bar:  baz
                another_input_processor:
                    foo:  bar
                input_processor_without_options: ~
            response: 
                my_response_processor:
                    format:   json
                    encoding: utf-8
                another_response_processor: ~
    
```

For details about how multiple Processors work together, see chapter 
[Using multiple Input and Response Processors](05-using-multiple-input-and-response-processors.md)

## Named Contexts
Similarly to the default Context, you can define any Context and give it whatever name you wish. 

```
# app/config/config.yml

bamiz_use_case:
    contexts:
        web:
            input:    http
            response: twig
        behat:
        	input:
        	    fixture:
                    strategy: random_values
        	    
    
```

It is only possible to use named Contexts in the `execute()` method of the Executor.

```
<?php
$executor->execute('my_use_case', $input, 'behat');
```
Any options provided in the named Context will **override** options from the custom Context, if one exists for the 
Use Case. In case one of the Processor is not configured, the Executor will fall back to the defaults.

Once you have some named Contexts configured, it is possible to specify the name of the Context that will serve as 
the default one:

```
# app/config/config.yml

bamiz_use_case:
    contexts:
        web:
            input:    http
            response: twig
    default_context: web

```

## Annotations

A Use Case-specific Context can be defined specifically for one Use Case using `@UseCase`, `@InputProcessor`, and
`@ResponseProcessor` annotations:

```
@UseCase("use_case_name")
@InputProcessor("http")
@ResponseProcessor("twig")
```

If you want to pass additional options to any Processor, you can do this just by adding these options to the annotation.

```
@UseCase("another_use_case_name")
@InputProcessor("my_input_processor", foo="bar")
@ResponseProcessor("my_response_processor", format="json", extra_fields={"foo"="bar"})
```

It is possible to assign multiple Processors to a single Use Case. For details about how multiple Processors work 
together, see chapter [Using multiple Input and Response Processors](05-using-multiple-input-and-response-processors.md).

In [the next chapter](04-toolkit.md) you will find a list of Input and Response Processors provided with the Use Case Bundle.

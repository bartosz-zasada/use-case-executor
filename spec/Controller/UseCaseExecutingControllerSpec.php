<?php

namespace spec\Bamiz\UseCaseBundle\Controller;

use Bamiz\UseCaseBundle\Execution\UseCaseExecutor;
use PhpSpec\ObjectBehavior;
use Bamiz\UseCaseBundle\Controller\UseCaseExecutingController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UseCaseExecutingControllerSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(UseCaseExecutingController::class);
    }

    public function it_extends_base_symfony_controller()
    {
        $this->shouldHaveType(Controller::class);
    }

    public function it_uses_magic_to_execute_use_cases(
        ContainerInterface $symfonyContainer,
        UseCaseExecutor $useCaseExecutor
    )
    {
        $symfonyContainer->get('bamiz_use_case.executor')->willReturn($useCaseExecutor);
        $this->setContainer($symfonyContainer);

        $useCaseExecutor->execute('do_something')->shouldBeCalled();

        $this->doSomething();
    }

    public function it_uses_magic_to_execute_use_cases_as_actors(
        ContainerInterface $symfonyContainer,
        UseCaseExecutor $useCaseExecutor,
        UseCaseExecutor $useCaseAsActorExecutor
    )
    {
        $symfonyContainer->get('bamiz_use_case.executor')->willReturn($useCaseExecutor);
        $this->setContainer($symfonyContainer);

        $useCaseExecutor->asActor('jedi')->willReturn($useCaseAsActorExecutor);
        $useCaseAsActorExecutor->execute('use_the_force', ['input' => 'http', 'response' => 'json'])->shouldBeCalled();

        $this->jedi->useTheForce(['input' => 'http', 'response' => 'json']);
    }
}

<?php
namespace Capersys\UserBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class ChangePasswordCommand extends Command
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var string
     */
    private $userClass;

    /**
     * @var UserPasswordEncoderInterface
     */
    private $encoder;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordEncoderInterface $encoder, string $userClass)
    {
        $this->em = $entityManager;
        $this->encoder = $encoder;
        $this->userClass = $userClass;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('user:changepassword')
            ->setDescription('User change password')
            ->addArgument('email', InputArgument::OPTIONAL, 'Email address')
            ->addArgument('password', InputArgument::OPTIONAL, 'User password');
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getArgument('email')) {
            $question = new Question('Email Address: ', 'demo@demo.com');
            $answer = $this->getHelper('question')->ask($input, $output, $question);
            $input->setArgument('email', $answer);
        }

        if (!$input->getArgument('password')) {
            $question = new Question('Password: ');
            $question->setValidator(function ($password) {
                if (empty($password)) {
                    throw new \RuntimeException('Password can not be empty');
                }

                return $password;
            });
            $answer = $this->getHelper('question')->ask($input, $output, $question);
            $input->setArgument('password', $answer);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Find User
        $user = $this->em->getRepository($this->userClass)->findOneBy(['email' => $input->getArgument('email')]);

        if (null !== $user) {
            // Set Password
            $password = $this->encoder->encodePassword($user, $input->getArgument('password'));
            $user->setPassword($password);

            // Save
            $this->em->persist($user);
            $this->em->flush();

            // Output
            $output->writeln('User Password Changed:');
            $output->writeln(sprintf('Email: <comment>%s</comment>', $user->getEmail()));
            $output->writeln(sprintf('Password: <comment>%s</comment>', $input->getArgument('password')));
        } else {
            $output->writeln('User not found!');
        }

        return 0;
    }
}
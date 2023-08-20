<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Translation\LocaleSwitcher;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Validation;
use function Symfony\Component\String\u;

/**
 * A console command that creates users and stores them in the database.
 *
 * To use this command, open a terminal window, enter into your project
 * directory and execute the following:
 *
 *     $ php bin/console drupguard:user:create
 *
 * To output detailed information, increase the command verbosity:
 *
 *     $ php bin/console drupguard:user:create -vv
 *
 * See https://symfony.com/doc/current/console.html
 *
 * We use the default services.yaml configuration, so command classes are registered as services.
 * See https://symfony.com/doc/current/console/commands_as_services.html
 */
#[AsCommand(
    name: 'drupguard:user:create',
    description: 'Creates users and stores them in the database'
)]
final class UserCreateCommand extends Command
{
    private SymfonyStyle $io;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UserRepository $users,
        private readonly EmailVerifier $emailVerifier,
        private readonly LocaleSwitcher $localeSwitcher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp($this->getCommandHelp())
            // commands can optionally define arguments and/or options (mandatory and optional)
            // see https://symfony.com/doc/current/components/console/console_arguments.html
            ->addArgument('username', InputArgument::OPTIONAL, 'The username of the new user')
            ->addArgument('password', InputArgument::OPTIONAL, 'The plain password of the new user')
            ->addArgument('email', InputArgument::OPTIONAL, 'The email of the new user')
            ->addOption('admin', null, InputOption::VALUE_NONE, 'If set, the user is created as an administrator')
            ->addOption('verified', null, InputOption::VALUE_NONE, 'If set, the user is considered as verified')
            ->addOption('password-validation-ignored', null, InputOption::VALUE_NONE, 'If set, the password constraints will not be checked')
            ->addOption('show-credentials', null, InputOption::VALUE_NONE, 'If set, the user credentials will be print after creation')
        ;
    }

    /**
     * This optional method is the first one executed for a command after configure()
     * and is useful to initialize properties based on the input arguments and options.
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        // SymfonyStyle is an optional feature that Symfony provides so you can
        // apply a consistent look to the commands of your application.
        // See https://symfony.com/doc/current/console/style.html
        $this->io = new SymfonyStyle($input, $output);
    }

    /**
     * This method is executed after initialize() and before execute(). Its purpose
     * is to check if some of the options/arguments are missing and interactively
     * ask the user for those values.
     *
     * This method is completely optional. If you are developing an internal console
     * command, you probably should not implement this method because it requires
     * quite a lot of work. However, if the command is meant to be used by external
     * users, this method is a nice way to fall back and prevent errors.
     */
    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        if (null !== $input->getArgument('username') && null !== $input->getArgument('password') && null !== $input->getArgument('email')) {
            return;
        }

        $this->io->title('Add User Command Interactive Wizard');
        $this->io->text([
            'If you prefer to not use this interactive wizard, provide the',
            'arguments required by this command as follows:',
            '',
            ' $ php bin/console drupguard:user:create username password email@example.com',
            '',
            'Now we\'ll ask you for the value of all the missing command arguments.',
        ]);

        // Ask for the username if it's not defined
        $username = $input->getArgument('username');
        if (null !== $username) {
            $this->io->text(' > <info>Username</info>: '.$username);
        } else {
            $username = $this->io->ask('Username', null, $this->validate_username());
            $input->setArgument('username', $username);
        }

        // Ask for the password if it's not defined
        /** @var string|null $password */
        $password = $input->getArgument('password');
        $isPasswordValidationIgnored = $input->getOption('password-validation-ignored');
        if (null !== $password) {
            $this->io->text(' > <info>Password</info>: '.u('*')->repeat(u($password)->length()));
        } else {
            $password = $this->io->askHidden('Password (your type will be hidden)', $this->validate_password($isPasswordValidationIgnored));
            $input->setArgument('password', $password);
        }

        // Ask for the email if it's not defined
        $email = $input->getArgument('email');
        if (null !== $email) {
            $this->io->text(' > <info>Email</info>: '.$email);
        } else {
            $email = $this->io->ask('Email', null, $this->validate_email());
            $input->setArgument('email', $email);
        }
    }

    protected function validate_username() {
        return function(string $val) :string {
            if (empty($val)) {
                throw new InvalidArgumentException('The username can not be empty.');
            }

            $validator = Validation::createValidator();
            $violations = $validator->validate($val, [
                new Regex(
                    message: 'The username must contain only lowercase latin characters and underscores.',
                    pattern: '/^[a-z_]+$/',
                    match: true
                ),
            ]);

            if (0 !== count($violations)) {
                // there are errors, now you can show them
                foreach ($violations as $violation) {
                    throw new InvalidArgumentException($violation->getMessage());
                }
            }

            return $val;
        };
    }
    protected function validate_password(bool $isPasswordValidationIgnored = false) {
        return function (string $val) use ($isPasswordValidationIgnored):string {
            if (empty($val)) {
                throw new InvalidArgumentException('The password can not be empty.');
            }
            if (!$isPasswordValidationIgnored) {
                $validator = Validation::createValidator();
                $violations = $validator->validate($val, [
                    new NotBlank(
                        message: 'Please enter a password',
                    ),
                    new Length(
                        min: 6,
                        minMessage: 'Your password should be at least {{ limit }} characters',
                        // max length allowed by Symfony for security reasons
                        max: 4096,
                    ),
                ]);

                if (0 !== count($violations)) {
                    // there are errors, now you can show them
                    foreach ($violations as $violation) {
                        throw new InvalidArgumentException($violation->getMessage());
                    }
                }
            }

            return $val;
        };
    }
    protected function validate_email() {
        return function (string $val):string {
            if (empty($val)) {
                throw new InvalidArgumentException('The email can not be empty.');
            }
            $validator = Validation::createValidator();
            $violations = $validator->validate($val, [
                new Email(),
            ]);

            if (0 !== count($violations)) {
                // there are errors, now you can show them
                foreach ($violations as $violation) {
                    throw new InvalidArgumentException($violation->getMessage());
                }
            }

            return $val;
        };
    }

    /**
     * This method is executed after interact() and initialize(). It usually
     * contains the logic to execute to complete this command task.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $stopwatch = new Stopwatch();
        $stopwatch->start('add-user-command');

        /** @var string $username */
        $username = $input->getArgument('username');

        /** @var string $plainPassword */
        $plainPassword = $input->getArgument('password');

        /** @var string $email */
        $email = $input->getArgument('email');

        $isAdmin = $input->getOption('admin');

        $isVerified = $input->getOption('verified');

        $isPasswordValidationIgnored = $input->getOption('password-validation-ignored');
        $showCredentials = $input->getOption('show-credentials');

        // make sure to validate the user data is correct
        $this->validateUserData($username, $plainPassword, $email, $isPasswordValidationIgnored);

        // create the user and hash its password
        $user = new User();
        $user->setUsername($username);
        $user->setEmail($email);
        if ($isAdmin) {
            $user->addRole('ROLE_ADMIN');
        }
        $user->setIsVerified($isVerified);

        // See https://symfony.com/doc/5.4/security.html#registering-the-user-hashing-passwords
        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
        if (!$isVerified) {
            $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                (new TemplatedEmail())
                    ->from(new Address('no-reply@drupguard.com', 'Drupguard'))
                    ->to($user->getEmail())
                    ->subject('Please Confirm your Email')
                    ->htmlTemplate('registration/confirmation_email.html.twig'),
                ['_locale' => $this->localeSwitcher->getLocale()]
            );
        }

        $this->io->success(sprintf('%s was successfully created: %s (%s)', $isAdmin ? 'Administrator user' : 'User', $user->getUsername(), $user->getEmail()));
        if ($showCredentials) {
            $this->io->info(sprintf('Credentials %s : %s', $user->getUsername(), $plainPassword));
        }
        $event = $stopwatch->stop('add-user-command');
        if ($output->isVerbose()) {
            $this->io->comment(sprintf('New user database id: %d / Elapsed time: %.2f ms / Consumed memory: %.2f MB', $user->getId(), $event->getDuration(), $event->getMemory() / (1024 ** 2)));
        }

        return Command::SUCCESS;
    }

    private function validateUserData(string $username, string $plainPassword, string $email, bool $isPasswordValidationIgnored): void
    {
        call_user_func($this->validate_username(),$username);
        call_user_func($this->validate_password($isPasswordValidationIgnored),$plainPassword);
        call_user_func($this->validate_email(), $email);

        // check if a user with the same username already exists.
        $existingUser = $this->users->findOneBy(['username' => $username]);
        if (null !== $existingUser) {
            throw new RuntimeException(sprintf('There is already a user registered with the "%s" username.', $username));
        }
    }

    /**
     * The command help is usually included in the configure() method, but when
     * it's too long, it's better to define a separate method to maintain the
     * code readability.
     */
    private function getCommandHelp(): string
    {
        return <<<'HELP'
            The <info>%command.name%</info> command creates new users and saves them in the database:

              <info>php %command.full_name%</info> <comment>username password email</comment>

            By default the command creates regular users. To create administrator users,
            add the <comment>--admin</comment> option:

              <info>php %command.full_name%</info> username password email <comment>--admin</comment>

            If you omit any of the three required arguments, the command will ask you to
            provide the missing values:

              # command will ask you for the email
              <info>php %command.full_name%</info> <comment>username password</comment>

              # command will ask you for the email and password
              <info>php %command.full_name%</info> <comment>username</comment>

              # command will ask you for all arguments
              <info>php %command.full_name%</info>
            HELP;
    }
}
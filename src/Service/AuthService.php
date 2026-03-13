<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Twig\Environment;

class AuthService
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private MailerInterface $mailer,
        private Environment $twig
    ) {
    }

    public function register(string $email, string $password): User
    {
        // Validation du format email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new CustomUserMessageAuthenticationException('L\'adresse email n\'est pas valide.');
        }

        // Politique de mot de passe renforcée
        if (strlen($password) < 8) {
            throw new CustomUserMessageAuthenticationException('Le mot de passe doit contenir au moins 8 caractères.');
        }
        if (!preg_match('/[A-Z]/', $password)) {
            throw new CustomUserMessageAuthenticationException('Le mot de passe doit contenir au moins une lettre majuscule.');
        }
        if (!preg_match('/[a-z]/', $password)) {
            throw new CustomUserMessageAuthenticationException('Le mot de passe doit contenir au moins une lettre minuscule.');
        }
        if (!preg_match('/[0-9]/', $password)) {
            throw new CustomUserMessageAuthenticationException('Le mot de passe doit contenir au moins un chiffre.');
        }
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            throw new CustomUserMessageAuthenticationException('Le mot de passe doit contenir au moins un caractère spécial.');
        }

        if ($this->userRepository->findByEmail($email)) {
            throw new CustomUserMessageAuthenticationException('Cet email est déjà utilisé.');
        }

        $user = new User();
        $user->setEmail($email);
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);
        $user->setVerificationCode($this->generateVerificationCode());
        $user->setVerificationCodeExpiresAt(new \DateTimeImmutable('+30 minutes'));

        $this->userRepository->save($user, true);
        $this->sendVerificationEmail($user);

        return $user;
    }

    public function verifyEmail(string $code): bool
    {
        $user = $this->userRepository->findByVerificationCode($code);

        if (!$user) {
            return false;
        }

        if ($user->isVerificationCodeExpired()) {
            // Code expiré : en générer un nouveau et renvoyer l'email
            $user->setVerificationCode($this->generateVerificationCode());
            $user->setVerificationCodeExpiresAt(new \DateTimeImmutable('+30 minutes'));
            $this->userRepository->save($user, true);
            $this->sendVerificationEmail($user);

            throw new CustomUserMessageAuthenticationException(
                'Le code de vérification a expiré. Un nouveau code vous a été envoyé par email.'
            );
        }

        $user->setIsVerified(true);
        $user->setVerificationCode(null);
        $user->setVerificationCodeExpiresAt(null);
        $this->userRepository->save($user, true);

        return true;
    }

    private function generateVerificationCode(): string
    {
        return substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(32))), 0, 32);
    }

    private function sendVerificationEmail(User $user): void
    {
        $html = $this->twig->render('emails/verification.html.twig', [
            'code' => $user->getVerificationCode()
        ]);

        $email = (new Email())
            ->from('noreply@bibliotheque.com')
            ->to($user->getEmail())
            ->subject('Vérification de votre compte Bibliothèque')
            ->html($html);

        $this->mailer->send($email);
    }
} 
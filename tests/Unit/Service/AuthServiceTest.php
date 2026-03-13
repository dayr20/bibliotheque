<?php

namespace App\Tests\Unit\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\AuthService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Twig\Environment;

class AuthServiceTest extends TestCase
{
    private AuthService $authService;
    private MockObject&UserRepository $userRepository;
    private MockObject&UserPasswordHasherInterface $passwordHasher;
    private MockObject&MailerInterface $mailer;
    private MockObject&Environment $twig;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->twig = $this->createMock(Environment::class);

        $this->authService = new AuthService(
            $this->userRepository,
            $this->passwordHasher,
            $this->mailer,
            $this->twig
        );
    }

    public function testRegisterSuccess(): void
    {
        $this->userRepository->method('findByEmail')->willReturn(null);
        $this->passwordHasher->method('hashPassword')->willReturn('hashed_password');
        $this->twig->method('render')->willReturn('<html>Code: 123</html>');

        $this->userRepository->expects($this->once())->method('save');
        $this->mailer->expects($this->once())->method('send');

        $user = $this->authService->register('test@example.com', 'StrongP@ss1');

        $this->assertEquals('test@example.com', $user->getEmail());
        $this->assertEquals('hashed_password', $user->getPassword());
        $this->assertFalse($user->isVerified());
        $this->assertNotNull($user->getVerificationCode());
        $this->assertNotNull($user->getVerificationCodeExpiresAt());
    }

    public function testRegisterWithInvalidEmail(): void
    {
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('email n\'est pas valide');

        $this->authService->register('not-an-email', 'StrongP@ss1');
    }

    public function testRegisterWithShortPassword(): void
    {
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('au moins 8 caractères');

        $this->authService->register('test@example.com', 'Short1!');
    }

    public function testRegisterWithNoUppercase(): void
    {
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('majuscule');

        $this->authService->register('test@example.com', 'nouppercase1!');
    }

    public function testRegisterWithNoLowercase(): void
    {
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('minuscule');

        $this->authService->register('test@example.com', 'NOLOWERCASE1!');
    }

    public function testRegisterWithNoDigit(): void
    {
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('chiffre');

        $this->authService->register('test@example.com', 'NoDigitHere!');
    }

    public function testRegisterWithNoSpecialChar(): void
    {
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('spécial');

        $this->authService->register('test@example.com', 'NoSpecial1A');
    }

    public function testRegisterWithDuplicateEmail(): void
    {
        $existingUser = new User();
        $this->userRepository->method('findByEmail')->willReturn($existingUser);

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('déjà utilisé');

        $this->authService->register('existing@example.com', 'StrongP@ss1');
    }

    public function testVerifyEmailSuccess(): void
    {
        $user = new User();
        $user->setVerificationCode('valid_code');
        $user->setVerificationCodeExpiresAt(new \DateTimeImmutable('+30 minutes'));

        $this->userRepository->method('findByVerificationCode')->willReturn($user);
        $this->userRepository->expects($this->once())->method('save');

        $result = $this->authService->verifyEmail('valid_code');

        $this->assertTrue($result);
        $this->assertTrue($user->isVerified());
        $this->assertNull($user->getVerificationCode());
        $this->assertNull($user->getVerificationCodeExpiresAt());
    }

    public function testVerifyEmailWithInvalidCode(): void
    {
        $this->userRepository->method('findByVerificationCode')->willReturn(null);

        $result = $this->authService->verifyEmail('invalid_code');

        $this->assertFalse($result);
    }

    public function testVerifyEmailWithExpiredCode(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setVerificationCode('expired_code');
        $user->setVerificationCodeExpiresAt(new \DateTimeImmutable('-1 minute'));

        $this->userRepository->method('findByVerificationCode')->willReturn($user);
        $this->twig->method('render')->willReturn('<html>New code</html>');

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('expiré');

        $this->authService->verifyEmail('expired_code');
    }
}

<?php

namespace App\Command;

use App\Entity\Genre;
use App\Entity\Manga;
use App\Entity\User;
use App\Security\Roles;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:init-database',
    description: 'Initialise la base de données avec les données de base',
)]
class InitDatabaseCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Création des genres
        $genres = ['Action', 'Aventure', 'Comédie', 'Drame', 'Fantasy', 'Horreur', 'Romance', 'Sci-Fi', 'Slice of Life', 'Sport'];
        
        foreach ($genres as $genreName) {
            if (!$this->entityManager->getRepository(Genre::class)->findOneBy(['name' => $genreName])) {
                $genre = new Genre();
                $genre->setName($genreName);
                $this->entityManager->persist($genre);
                $io->text("Genre créé : $genreName");
            }
        }

        // Création d'un manga de test
        if (!$this->entityManager->getRepository(Manga::class)->findOneBy(['title' => 'One Piece'])) {
            $manga = new Manga();
            $manga->setTitle('One Piece');
            $manga->setAuthor('Eiichiro Oda');
            $manga->setDescription('L\'histoire suit les aventures de Monkey D. Luffy, un jeune garçon dont le corps a acquis les propriétés du caoutchouc après avoir mangé un fruit du démon. Avec son équipage de pirates, appelé l\'équipage de Chapeau de paille, Luffy explore Grand Line à la recherche du trésor ultime connu sous le nom de "One Piece" afin de devenir le prochain Roi des Pirates.');
            $manga->setRating(9.5);
            $manga->setIsNew(true);
            $manga->setCoverImage('https://uploads.mangadex.org/covers/a1c7c817-4e59-43b7-9365-09675a149a6f/4b5ba2ba-146c-4d65-988c-2d78b4aef55c.jpg');

            // Ajout des genres au manga
            $actionGenre = $this->entityManager->getRepository(Genre::class)->findOneBy(['name' => 'Action']);
            $aventureGenre = $this->entityManager->getRepository(Genre::class)->findOneBy(['name' => 'Aventure']);
            if ($actionGenre) $manga->addGenre($actionGenre);
            if ($aventureGenre) $manga->addGenre($aventureGenre);

            $this->entityManager->persist($manga);
            $io->text('Manga de test créé : One Piece');
        }

        // Création d'un compte admin
        if (!$this->entityManager->getRepository(User::class)->findOneBy(['email' => 'admin@example.com'])) {
            $admin = new User();
            $admin->setEmail('admin@example.com');
            $admin->setRoles([Roles::ROLE_ADMIN]);
            $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
            $admin->setIsVerified(true);
            
            $this->entityManager->persist($admin);
            $io->text('Compte admin créé (admin@example.com / admin123)');
        }

        $this->entityManager->flush();

        $io->success('La base de données a été initialisée avec succès !');

        return Command::SUCCESS;
    }
} 
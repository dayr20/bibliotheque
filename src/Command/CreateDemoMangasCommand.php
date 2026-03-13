<?php

namespace App\Command;

use App\Entity\Manga;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:create-demo-mangas',
    description: 'Creates demo mangas'
)]
class CreateDemoMangasCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $mangas = [
            [
                'title' => 'One Piece',
                'author' => 'Eiichiro Oda',
                'description' => 'L\'histoire suit les aventures de Monkey D. Luffy, un jeune pirate dont le corps a acquis les propriétés du caoutchouc après avoir mangé un fruit du démon. Avec son équipage de pirates, appelé l\'équipage de Chapeau de paille, Luffy explore Grand Line à la recherche du trésor ultime connu sous le nom de "One Piece" afin de devenir le prochain Roi des Pirates.',
                'coverImage' => 'https://uploads.mangadex.org/covers/a1c7c817-4e59-43b7-9365-09675a149a6f/b31a334d-6db6-4b94-8b43-2d6e87de24d6',
                'rating' => 4.8,
                'isNew' => true
            ],
            [
                'title' => 'Vagabond',
                'author' => 'Takehiko Inoue',
                'description' => 'Vagabond est une série de manga basée sur l\'histoire romancée de la vie de Miyamoto Musashi, légendaire escrimeur japonais et auteur du Livre des cinq anneaux. Le manga suit son parcours depuis sa jeunesse jusqu\'à sa maîtrise du sabre.',
                'coverImage' => 'https://uploads.mangadex.org/covers/d1a9fdeb-3048-4315-8b44-4f87d037f3e9/5f3f9c4b-2e0d-4734-8883-823fc0d34f8f',
                'rating' => 4.7,
                'isNew' => false
            ],
            [
                'title' => 'Monster',
                'author' => 'Naoki Urasawa',
                'description' => 'Le Dr. Kenzo Tenma est un brillant neurochirurgien japonais qui exerce en Allemagne. Sa vie bascule le jour où il choisit de sauver un jeune garçon plutôt que le maire de la ville. Des années plus tard, il découvre que l\'enfant qu\'il a sauvé est devenu un dangereux tueur en série.',
                'coverImage' => 'https://uploads.mangadex.org/covers/f6850c91-f750-4c0b-819a-f701f8a0413c/1a05018e-a37f-4b0c-8c43-7be241ec40b4',
                'rating' => 4.9,
                'isNew' => false
            ],
            [
                'title' => 'Fullmetal Alchemist',
                'author' => 'Hiromu Arakawa',
                'description' => 'Dans un monde où l\'alchimie est une science exacte, deux frères, Edward et Alphonse Elric, tentent de ressusciter leur mère en utilisant l\'alchimie, violant ainsi le tabou ultime. L\'expérience tourne mal, et ils en payent le prix fort. Ils partent alors à la recherche de la Pierre Philosophale pour retrouver leurs corps d\'origine.',
                'coverImage' => 'https://uploads.mangadex.org/covers/dd8a907a-3850-4f95-ba03-ba201a8399e3/ce8c8b76-9e4e-4c3c-9268-7c6f078c958e',
                'rating' => 4.8,
                'isNew' => true
            ],
            [
                'title' => 'Naruto',
                'author' => 'Masashi Kishimoto',
                'description' => 'L\'histoire suit Naruto Uzumaki, un jeune ninja qui recherche la reconnaissance de ses pairs et rêve de devenir Hokage, le chef de son village. L\'histoire est divisée en deux parties, la première se déroulant durant les années d\'adolescence de Naruto, et la seconde durant ses années de jeune adulte.',
                'coverImage' => 'https://uploads.mangadex.org/covers/0496e7c0-0808-4399-9322-7a4dd4d8a074/5c6b6291-dc71-4a42-9200-3780b7f203e9',
                'rating' => 4.6,
                'isNew' => false
            ]
        ];

        // Supprimer tous les mangas existants
        $this->entityManager->createQuery('DELETE FROM App\Entity\Manga')->execute();

        // Créer les nouveaux mangas
        foreach ($mangas as $mangaData) {
            $manga = new Manga();
            $manga->setTitle($mangaData['title']);
            $manga->setAuthor($mangaData['author']);
            $manga->setDescription($mangaData['description']);
            $manga->setCoverImage($mangaData['coverImage']);
            $manga->setRating($mangaData['rating']);
            $manga->setIsNew($mangaData['isNew']);

            $this->entityManager->persist($manga);
        }

        $this->entityManager->flush();

        $output->writeln('Mangas de démonstration créés avec succès !');

        return Command::SUCCESS;
    }
} 
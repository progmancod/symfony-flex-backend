<?php
declare(strict_types=1);
/**
 * /src/Command/Utils/CreateDateDimensionEntitiesCommand.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Command\Utils;

use App\Entity\DateDimension;
use App\Repository\DateDimensionRepository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class CreateDateDimensionEntitiesCommand
 *
 * @package App\Command\Utils
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class CreateDateDimensionEntitiesCommand extends ContainerAwareCommand
{
    const YEAR_MIN = 1970;
    const YEAR_MAX = 2047; // This should be the year when I'm officially retired

    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * @var DateDimensionRepository
     */
    private $repository;

    /**
     * PopulateDateDimensionCommand constructor.
     *
     * @param DateDimensionRepository $dateDimensionRepository
     *
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct(DateDimensionRepository $dateDimensionRepository)
    {
        parent::__construct('utils:create-date-dimension-entities');

        $this->repository = $dateDimensionRepository;

        $this->setDescription('Console command to create \'DateDimension\' entities.');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * Executes the current command.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return null|int null or 0 if everything went fine, or an error code
     *
     * @throws \Exception
     * @throws \InvalidArgumentException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        // Create output decorator helpers for the Symfony Style Guide.
        $this->io = new SymfonyStyle($input, $output);

        // Set title
        $this->io->title($this->getDescription());

        // Determine start and end years
        $yearStart = $this->getYearStart();
        $yearEnd = $this->getYearEnd($yearStart);

        // Create actual entities
        $this->createEntities($yearStart, $yearEnd);

        $this->io->success('All done - have a nice day!');

        return null;
    }

    /**
     * Method to get start year value from user.
     *
     * @return int
     *
     * @throws \InvalidArgumentException
     */
    private function getYearStart(): int
    {
        /**
         * Lambda validator function for start year io question.
         *
         * @param mixed $year
         *
         * @return int
         */
        $validator = function ($year) {
            $year = (int)$year;

            if ($year < self::YEAR_MIN || $year > self::YEAR_MAX) {
                $message = \sprintf(
                    'Start year must be between %d and %d',
                    self::YEAR_MIN,
                    self::YEAR_MAX
                );

                throw new \InvalidArgumentException($message);
            }

            return $year;
        };

        return (int)$this->io->ask('Give a year where to start', self::YEAR_MIN, $validator);
    }

    /**
     * Method to get end year value from user.
     *
     * @param int $yearStart
     *
     * @return int
     *
     * @throws \InvalidArgumentException
     */
    private function getYearEnd(int $yearStart): int
    {
        /**
         * Lambda validator function for end year io question.
         *
         * @param mixed $year
         *
         * @return int
         */
        $validator = function ($year) use ($yearStart) {
            $year = (int)$year;

            if ($year < self::YEAR_MIN || $year > self::YEAR_MAX) {
                $message = \sprintf(
                    'End year must be between %d and %d',
                    self::YEAR_MIN,
                    self::YEAR_MAX
                );

                throw new \InvalidArgumentException($message);
            }

            if ($year < $yearStart) {
                throw new \InvalidArgumentException('End year cannot be before given start year');
            }

            return $year;
        };

        return (int)$this->io->ask('Give a year where to end', self::YEAR_MAX, $validator);
    }

    /**
     * Method to create DateDimension entities to database.
     *
     * @param int $yearStart
     * @param int $yearEnd
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Exception
     */
    private function createEntities(int $yearStart, int $yearEnd)
    {
        $dateStart = new \DateTime($yearStart . '-01-01 00:00:00', new \DateTimeZone('UTC'));
        $dateEnd = new \DateTime($yearEnd . '-12-31 00:00:00', new \DateTimeZone('UTC'));

        $progress = $this->getProgressBar(
            (int)$dateEnd->diff($dateStart)->format('%a') + 1,
            \sprintf('Creating DateDimension entities between years %d and %d...', $yearStart, $yearEnd)
        );

        // Remove existing entities
        $this->repository->reset();

        // Get entity manager for _fast_ database handling.
        $em = $this->repository->getEntityManager();

        // You spin me round (like a record... er like a date)
        while (true) {
            if ((int)$dateStart->format('Y') > $yearEnd) {
                break;
            }

            $em->persist(new DateDimension(clone $dateStart));

            $dateStart->add(new \DateInterval('P1D'));

            // Flush whole year of entities at one time
            if ($progress->getProgress() % 1000 === 0) {
                $em->flush();
                $em->clear();
            }

            $progress->advance();
        }

        // Finally flush remaining entities
        $em->flush();
        $em->clear();
    }

    /**
     * Helper method to get progress bar for console.
     *
     * @param   int     $steps
     * @param   string  $message
     *
     * @return  ProgressBar
     */
    private function getProgressBar(int $steps, string $message): ProgressBar
    {
        $format = '
 %message%
 %current%/%max% [%bar%] %percent:3s%%
 Time elapsed:   %elapsed:-6s%
 Time remaining: %remaining:-6s%
 Time estimated: %estimated:-6s%
 Memory usage:   %memory:-6s%
';

        $progress = $this->io->createProgressBar($steps);
        $progress->setFormat($format);
        $progress->setMessage($message);

        return $progress;
    }
}
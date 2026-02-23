<?php

namespace App\Command;

use App\Service\MidnightRechargeService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:game:reset-sunday',
    description: 'Reset les jours disponibles non utilisés chaque dimanche (à exécuter le dimanche à 00h00)'
)]
class GameResetSundayCommand extends Command
{
    public function __construct(
        private readonly MidnightRechargeService $midnightRechargeService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Reset des jours disponibles (dimanche)');

        $reset = $this->midnightRechargeService->resetSunday();

        if ($reset > 0) {
            $io->success(sprintf('%d entreprise(s) ont eu leurs jours disponibles réinitialisés.', $reset));
        } else {
            $now = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
            if ((int)$now->format('w') !== 0) {
                $io->warning('Ce n\'est pas dimanche. Aucune action effectuée.');
            } else {
                $io->info('Aucune entreprise n\'avait de jours disponibles à réinitialiser.');
            }
        }

        return Command::SUCCESS;
    }
}

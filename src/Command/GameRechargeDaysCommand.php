<?php

namespace App\Command;

use App\Service\MidnightRechargeService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:game:recharge-days',
    description: 'Recharge les jours disponibles pour toutes les entreprises (à exécuter à 00h00)'
)]
class GameRechargeDaysCommand extends Command
{
    public function __construct(
        private readonly MidnightRechargeService $midnightRechargeService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Recharge des jours disponibles');

        $processed = $this->midnightRechargeService->rechargeDays();

        if ($processed > 0) {
            $io->success(sprintf('%d entreprise(s) ont été rechargées.', $processed));
        } else {
            $io->info('Aucune entreprise n\'a nécessité de recharge.');
        }

        return Command::SUCCESS;
    }
}

<?php

namespace App\Service;

class EmployeeNameGenerator
{
    private const FIRST_NAMES = [
        'Alexandre', 'Antoine', 'Baptiste', 'Benjamin', 'Camille', 'Cédric', 'Clément', 'David',
        'Élise', 'Emma', 'Florian', 'Gabriel', 'Hugo', 'Julien', 'Laura', 'Lucas',
        'Marie', 'Mathieu', 'Maxime', 'Nicolas', 'Paul', 'Pierre', 'Sophie', 'Thomas',
        'Vincent', 'Yann', 'Amélie', 'Charlotte', 'Julie', 'Manon', 'Sarah', 'Léa'
    ];

    private const LAST_NAMES = [
        'Martin', 'Bernard', 'Dubois', 'Thomas', 'Robert', 'Richard', 'Petit', 'Durand',
        'Leroy', 'Moreau', 'Simon', 'Laurent', 'Lefebvre', 'Michel', 'Garcia', 'David',
        'Bertrand', 'Roux', 'Vincent', 'Fournier', 'Morel', 'Girard', 'André', 'Lefevre',
        'Mercier', 'Dupont', 'Lambert', 'Bonnet', 'François', 'Martinez', 'Legrand', 'Garnier'
    ];

    /**
     * Génère un nom aléatoire (prénom + nom)
     */
    public function generate(): string
    {
        $firstName = self::FIRST_NAMES[array_rand(self::FIRST_NAMES)];
        $lastName = self::LAST_NAMES[array_rand(self::LAST_NAMES)];
        
        return $firstName . ' ' . $lastName;
    }
}

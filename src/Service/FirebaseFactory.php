<?php

namespace App\Service;

use Kreait\Firebase\Database;
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use Psr\Log\LoggerInterface;

class FirebaseFactory
{
    public static function createFirebaseDatabase(
        LoggerInterface $logger,
        ?string $projectId,
        ?string $clientId,
        ?string $clientEmail,
        ?string $privateKey,
        ?string $dbUri
    ): ?Database {
        if (empty($projectId) || empty($clientId) || empty($clientEmail)
            || empty($privateKey) || empty($dbUri)
        ) {
            $logger->debug('Firebase not configured');

            return null;
        }

        $pKey = preg_replace('/\\\\n/', chr(10), $privateKey);

        $serviceAccount = ServiceAccount::fromArray([
            'project_id' => $projectId,
            'client_id' => $clientId,
            'client_email' => $clientEmail,
            'private_key' => $pKey,
        ]);

        return (new Factory())
            ->withServiceAccount($serviceAccount)
            ->withDatabaseUri($dbUri)
            ->createDatabase();
    }
}

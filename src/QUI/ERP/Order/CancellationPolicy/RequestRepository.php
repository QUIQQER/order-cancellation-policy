<?php

namespace QUI\ERP\Order\CancellationPolicy;

use QUI;

class RequestRepository
{
    protected static function table(): string
    {
        return QUI::getDBTableName('requests');
    }

    /**
     * @param array<string, scalar|null> $data
     * @throws QUI\Database\Exception
     */
    public static function create(
        array $data,
        ?QUI\Projects\Project $Project = null,
        ?QUI\Projects\Site $Site = null
    ): void {
        QUI::getDataBase()->insert(
            self::table(),
            [
                'project' => $Project?->getName(),
                'lang' => $Project?->getLang(),
                'site_id' => $Site?->getId(),
                'created_at' => date('Y-m-d H:i:s'),
                'first_name' => $data['firstName'],
                'last_name' => $data['lastName'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?: null,
                'order_no' => $data['orderNo'],
                'order_date' => $data['orderDate'] ?: null,
                'message' => $data['message'],
                'privacy_policy_accepted' => (int)$data['privacyPolicyAccepted'],
                'status' => 'new'
            ]
        );
    }
}

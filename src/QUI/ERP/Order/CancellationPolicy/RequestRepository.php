<?php

namespace QUI\ERP\Order\CancellationPolicy;

use QUI;
use QUI\Utils\Doctrine;

class RequestRepository
{
    protected static function table(): string
    {
        return QUI::getDBTableName('requests');
    }

    /**
     * @param array{
     *     firstName: string,
     *     lastName: string,
     *     email: string,
     *     phone: string,
     *     orderNo: string,
     *     orderDate: string,
     *     message: string,
     *     privacyPolicyAccepted: bool,
     *     captchaResponse: string
     * } $data
     * @throws \Doctrine\DBAL\Exception
     */
    public static function create(
        array $data,
        ?QUI\Projects\Project $Project = null,
        ?QUI\Projects\Site $Site = null
    ): void {
        QUI::getDataBaseConnection()->insert(
            Doctrine::quoteIdentifier(self::table()),
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

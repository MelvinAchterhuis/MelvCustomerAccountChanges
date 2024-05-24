<?php declare(strict_types=1);

namespace Melv\CustomerAccountChanges\Migration;

use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Shopware\Core\Content\Flow\Aggregate\FlowTemplate\FlowTemplateDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * @internal
 */
#[Package('core')]
class Migration1715197845addPasswordChangedMailTemplate extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1715197845;
    }

    /**
     * @throws Exception
     */
    public function update(Connection $connection): void
    {
        $mailTemplateId = Uuid::randomHex();
        $mailTemplateTypeId = $this->createMailTemplateType($connection);

        $this->createMailTemplate($connection, $mailTemplateId, $mailTemplateTypeId);
        $this->insertFlowData($connection, $mailTemplateId);
        $this->insertFlowTemplateData($connection, $mailTemplateId);

        $this->registerIndexer($connection, 'flow.indexer');
    }

    private function createMailTemplateType(Connection $connection): string
    {
        $mailTemplateTypeId = Uuid::randomHex();

        $enGbLangId = $this->getLanguageIdByLocale($connection, 'en-GB');
        $deDeLangId = $this->getLanguageIdByLocale($connection, 'de-DE');

        $englishName = 'Customer changed password';
        $germanName = 'Kundenpasswort wurde geändert';

        $connection->executeStatement("
            INSERT INTO `mail_template_type`
                (id, technical_name, available_entities, created_at)
            VALUES
                (:id, :technicalName, :availableEntities, :createdAt)
        ",[
            'id' => Uuid::fromHexToBytes($mailTemplateTypeId),
            'technicalName' => 'checkout_customer_changed_password',
            'availableEntities' => json_encode(['product' => 'product']),
            'createdAt' => (new DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);

        if (!empty($enGbLangId)) {
            $connection->executeStatement("
            INSERT INTO `mail_template_type_translation`
                (mail_template_type_id, language_id, name, created_at)
            VALUES
                (:mailTemplateTypeId, :languageId, :name, :createdAt)
            ",[
                'mailTemplateTypeId' => Uuid::fromHexToBytes($mailTemplateTypeId),
                'languageId' => $enGbLangId,
                'name' => $englishName,
                'createdAt' => (new DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]);
        }

        if (!empty($deDeLangId)) {
            $connection->executeStatement("
            INSERT INTO `mail_template_type_translation`
                (mail_template_type_id, language_id, name, created_at)
            VALUES
                (:mailTemplateTypeId, :languageId, :name, :createdAt)
            ",[
                'mailTemplateTypeId' => Uuid::fromHexToBytes($mailTemplateTypeId),
                'languageId' => $deDeLangId,
                'name' => $germanName,
                'createdAt' => (new DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]);
        }

        return $mailTemplateTypeId;
    }

    private function createMailTemplate(Connection $connection, string $mailTemplateId, string $mailTemplateTypeId): void
    {
        $enGbLangId = $this->getLanguageIdByLocale($connection, 'en-GB');
        $deDeLangId = $this->getLanguageIdByLocale($connection, 'de-DE');

        $connection->executeStatement("
        INSERT INTO `mail_template`
            (id, mail_template_type_id, system_default, created_at)
        VALUES
            (:id, :mailTemplateTypeId, :systemDefault, :createdAt)
        ",[
            'id' => Uuid::fromHexToBytes($mailTemplateId),
            'mailTemplateTypeId' => Uuid::fromHexToBytes($mailTemplateTypeId),
            'systemDefault' => 0,
            'createdAt' => (new DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);

        if (!empty($enGbLangId)) {
            $connection->executeStatement("
            INSERT INTO `mail_template_translation`
                (mail_template_id, language_id, sender_name, subject, description, content_html, content_plain, created_at)
            VALUES
                (:mailTemplateId, :languageId, :senderName, :subject, :description, :contentHtml, :contentPlain, :createdAt)
            ",[
                'mailTemplateId' => Uuid::fromHexToBytes($mailTemplateId),
                'languageId' => $enGbLangId,
                'senderName' => '{{ salesChannel.name }}',
                'subject' => 'Your password has changed',
                'description' => 'Customer password has changed',
                'contentHtml' => $this->getContentHtmlEn(),
                'contentPlain' => $this->getContentPlainEn(),
                'createdAt' => (new DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]);
        }

        if (!empty($deDeLangId)) {
            $connection->executeStatement("
            INSERT INTO `mail_template_translation`
                (mail_template_id, language_id, sender_name, subject, description, content_html, content_plain, created_at)
            VALUES
                (:mailTemplateId, :languageId, :senderName, :subject, :description, :contentHtml, :contentPlain, :createdAt)
            ",[
                'mailTemplateId' => Uuid::fromHexToBytes($mailTemplateId),
                'languageId' => $deDeLangId,
                'senderName' => '{{ salesChannel.name }}',
                'subject' => 'Ihr Passwort hat sich geändert',
                'description' => 'Das Kundenpasswort wurde geändert',
                'contentHtml' => $this->getContentHtmlDe(),
                'contentPlain' => $this->getContentPlainDe(),
                'createdAt' => (new DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]);
        }
    }

    private function getLanguageIdByLocale(Connection $connection, string $locale): ?string
    {
        $sql = <<<SQL
        SELECT `language`.`id`
        FROM `language`
        INNER JOIN `locale` ON `locale`.`id` = `language`.`locale_id`
        WHERE `locale`.`code` = :code
        SQL;

        $languageId = $connection->executeQuery($sql, ['code' => $locale])->fetchOne();

        if (empty($languageId)) {
            return null;
        }

        return $languageId;
    }

    private function getContentHtmlEn(): string
    {
        return <<<MAIL
<div style="font-family:arial; font-size:12px;">
    <p>
        Hello {{ customer.firstName }},<br/>
        <br/>
        You have requested a new password for your account.
        If you did not make this change, please contact our support<br/>
        <br/>
        Yours sincerely
        Your {{ salesChannel.name }} team
    </p>
</div>
MAIL;
    }

    private function getContentPlainEn(): string
    {
        return <<<MAIL
        Hello {{ customer.firstName }},

        You have requested a new password for your account.
        If you did not make this change, please contact our support

        Yours sincerely
        Your {{ salesChannel.name }} team
MAIL;
    }

    private function getContentHtmlDe(): string
    {
        return <<<MAIL
<div style="font-family:arial; font-size:12px;">
    <p>
        Hallo {{ customer.firstName }},<br/>
        <br/>
        Sie haben ein neues Passwort für Ihr Konto angefordert.
        Wenn Sie diese Änderung nicht vorgenommen haben, wenden Sie sich bitte an unseren Support<br/>
        <br/>
        Mit freundlichen Grüßen
        Dein {{ salesChannel.name }}-Team
    </p>
</div>
MAIL;
    }

    private function getContentPlainDe(): string
    {
        return <<<MAIL
        Hallo {{ customer.firstName }},

        Sie haben ein neues Passwort für Ihr Konto angefordert.
        Wenn Sie diese Änderung nicht vorgenommen haben, wenden Sie sich bitte an unseren Support

        Mit freundlichen Grüßen
        Dein {{ salesChannel.name }}-Team
MAIL;
    }

    /**
     * @param Connection $connection
     * @param string $mailTemplateId
     * @throws Exception
     */
    private function insertFlowData(Connection $connection, string $mailTemplateId): void
    {
        $flowId = $connection->fetchOne('SELECT id FROM flow WHERE name = :name', ['name' => 'Customer changed password']);

        if ($flowId) {
            return;
        }

        $flowId = Uuid::randomBytes();

        $connection->insert(
            'flow',
            [
                'id' => $flowId,
                'name' => 'Customer changed password',
                'event_name' => 'checkout.customer.changed-password',
                'active' => 0,
                'payload' => null,
                'invalid' => 0,
                'custom_fields' => null,
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]
        );

        $connection->insert(
            'flow_sequence',
            [
                'id' => Uuid::randomBytes(),
                'flow_id' => $flowId,
                'rule_id' => null,
                'parent_id' => null,
                'action_name' => 'action.mail.send',
                'position' => 1,
                'true_case' => 0,
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                'config' => sprintf(
                    '{"recipient": {"data": [], "type": "default"}, "mailTemplateId": "%s", "documentTypeIds": []}',
                    $mailTemplateId
                ),
            ]
        );
    }

    /**
     * @param Connection $connection
     * @param string $mailTemplateId
     * @throws Exception
     */
    private function insertFlowTemplateData(Connection $connection, string $mailTemplateId): void
    {
        $flowTemplateId = $connection->fetchOne('SELECT id FROM flow_template WHERE name = :name', ['name' => 'Customer changed password']);

        if ($flowTemplateId) {
            return;
        }

        $sequenceConfig[] = [
            'id' => Uuid::randomHex(),
            'actionName' => 'action.mail.send',
            'config' => sprintf(
                '{"recipient": {"data": [], "type": "default"}, "mailTemplateId": "%s", "documentTypeIds": []}',
                $mailTemplateId
            ),
            'parentId' => null,
            'ruleId' => null,
            'position' => 1,
            'trueCase' => 0,
            'displayGroup' => 1,
        ];

        $connection->insert(FlowTemplateDefinition::ENTITY_NAME, [
            'id' => Uuid::randomBytes(),
            'name' => 'Customer changed password',
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            'config' => json_encode([
                'eventName' => 'checkout.customer.changed-password',
                'description' => null,
                'customFields' => null,
                'sequences' => $sequenceConfig,
            ]),
        ]);
    }
}

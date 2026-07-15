<?php

namespace QUITests\ERP\Order\CancellationPolicy;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\ERP\Order\CancellationPolicy\FormSubmission;
use QUI\Mail\Mailer;
use QUI\Mail\Manager as MailManager;
use QUI\Projects\Site;
use QUI\Utils\Doctrine;
use Throwable;

class FormSubmissionDatabaseTest extends TestCase
{
    private const EMAIL_PREFIX = 'phpunit-cancellation-';

    private MailManager $originalMailManager;
    private mixed $originalFrontendConfig;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalMailManager = QUI::getMailManager();
        $Config = QUI::getPackage('quiqqer/order-cancellation-policy')->getConfig();
        self::assertNotNull($Config);
        $this->originalFrontendConfig = $Config->getSection('frontend');
        $Config->setValue('frontend', 'useCaptcha', 0);
        $Config->setValue('frontend', 'mailTo', 'cancellation@example.test');
        $Config->save();
        $this->deleteRequests();
    }

    protected function tearDown(): void
    {
        $this->deleteRequests();
        QUI::$MailManager = $this->originalMailManager;
        $Config = QUI::getPackage('quiqqer/order-cancellation-policy')->getConfig();

        if ($Config) {
            $Config->setSection(
                'frontend',
                is_array($this->originalFrontendConfig) ? $this->originalFrontendConfig : []
            );
            $Config->save();
        }

        parent::tearDown();
    }

    public function testValidSubmissionIsStoredAndMailed(): void
    {
        $email = self::EMAIL_PREFIX . bin2hex(random_bytes(6)) . '@example.test';
        $body = null;
        $subject = null;
        $Mailer = $this->createMock(Mailer::class);
        $Mailer->expects(self::once())->method('addRecipient')->with('cancellation@example.test');
        $Mailer->expects(self::once())->method('addReplyTo')->with($email);
        $Mailer->method('setSubject')->willReturnCallback(
            static function (string $value) use (&$subject): void {
                $subject = $value;
            }
        );
        $Mailer->method('setBody')->willReturnCallback(
            static function (string $value) use (&$body): void {
                $body = $value;
            }
        );
        $Mailer->expects(self::once())->method('send')->willReturn(true);
        $MailManager = $this->createMock(MailManager::class);
        $MailManager->method('getMailer')->willReturn($Mailer);
        QUI::$MailManager = $MailManager;
        $Project = QUI::getProjectManager()->getStandard();
        self::assertNotNull($Project);
        $Site = $this->createMock(Site::class);
        $Site->method('getId')->willReturn(73);
        $Site->method('getAttribute')->with('title')->willReturn('PHPUnit cancellation page');

        FormSubmission::submit([
            'firstName' => '  Erika ',
            'lastName' => ' Mustermann  ',
            'email' => $email,
            'phone' => ' 01234  ',
            'orderNo' => ' ORDER-42 ',
            'orderDate' => '2026-07-15',
            'message' => 'Cancel <script>alert(1)</script>',
            'privacyPolicyAccepted' => true
        ], $Project, $Site);

        $row = $this->findRequest($email);
        self::assertSame('Erika', $row['first_name']);
        self::assertSame('Mustermann', $row['last_name']);
        self::assertSame('01234', $row['phone']);
        self::assertSame('ORDER-42', $row['order_no']);
        self::assertSame('2026-07-15', $row['order_date']);
        self::assertSame('new', $row['status']);
        self::assertSame(1, (int)$row['privacy_policy_accepted']);
        self::assertIsString($subject);
        self::assertStringContainsString('PHPUnit cancellation page', $subject);
        self::assertIsString($body);
        self::assertStringContainsString('&lt;script&gt;', $body);
        self::assertStringNotContainsString('<script>', $body);
    }

    /**
     * @param array<string, mixed> $changes
     */
    #[DataProvider('invalidSubmissionProvider')]
    public function testRejectsInvalidSubmission(array $changes): void
    {
        $data = array_replace($this->validData(), $changes);

        $this->expectException(QUI\Exception::class);
        FormSubmission::submit($data);
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function invalidSubmissionProvider(): iterable
    {
        yield 'missing first name' => [['firstName' => '']];
        yield 'invalid email' => [['email' => 'not-an-email']];
        yield 'invalid date' => [['orderDate' => '2026-02-30']];
        yield 'missing privacy acceptance' => [['privacyPolicyAccepted' => false]];
        yield 'oversized message' => [['message' => str_repeat('x', 5001)]];
    }

    /**
     * @return array<string, mixed>
     */
    private function validData(): array
    {
        return [
            'firstName' => 'Erika',
            'lastName' => 'Mustermann',
            'email' => self::EMAIL_PREFIX . 'validation@example.test',
            'phone' => '',
            'orderNo' => 'ORDER-42',
            'orderDate' => '',
            'message' => 'Please cancel the order.',
            'privacyPolicyAccepted' => true,
            'captchaResponse' => ''
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function findRequest(string $email): array
    {
        $row = QUI::getDataBaseConnection()->createQueryBuilder()
            ->select('*')
            ->from($this->getTable())
            ->where(Doctrine::quoteIdentifier('email') . ' = :email')
            ->setParameter('email', $email)
            ->executeQuery()
            ->fetchAssociative();

        self::assertIsArray($row);

        return $row;
    }

    private function deleteRequests(): void
    {
        try {
            QUI::getDataBaseConnection()->createQueryBuilder()
                ->delete($this->getTable())
                ->where(Doctrine::quoteIdentifier('email') . ' LIKE :email')
                ->setParameter('email', self::EMAIL_PREFIX . '%')
                ->executeStatement();
        } catch (Throwable) {
        }
    }

    private function getTable(): string
    {
        return Doctrine::quoteIdentifier(QUI::getDBTableName('requests'));
    }
}

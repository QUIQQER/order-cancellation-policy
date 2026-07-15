<?php

namespace QUITests\ERP\Order\CancellationPolicy;

use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Captcha\Controls\CaptchaDisplay;
use QUI\ERP\Order\CancellationPolicy\CancellationFormHelper;
use QUI\Projects\Project;
use QUI\Projects\Site;
use ReflectionProperty;

class CancellationFormHelperIntegrationTest extends TestCase
{
    private mixed $originalFrontendConfig;
    private mixed $originalSessionUser;

    protected function setUp(): void
    {
        parent::setUp();
        $Config = QUI::getPackage('quiqqer/order-cancellation-policy')->getConfig();
        self::assertNotNull($Config);
        $this->originalFrontendConfig = $Config->getSection('frontend');
        $Users = QUI::getUsers();
        $SessionUser = new ReflectionProperty($Users, 'Session');
        $this->originalSessionUser = $SessionUser->getValue($Users);
    }

    protected function tearDown(): void
    {
        $Config = QUI::getPackage('quiqqer/order-cancellation-policy')->getConfig();

        if ($Config) {
            $Config->setSection(
                'frontend',
                is_array($this->originalFrontendConfig) ? $this->originalFrontendConfig : []
            );
            $Config->save();
        }

        (new ReflectionProperty(QUI::getUsers(), 'Session'))->setValue(
            QUI::getUsers(),
            $this->originalSessionUser
        );
        parent::tearDown();
    }

    public function testReadsSettingsAndConfiguredMailRecipient(): void
    {
        $Config = QUI::getPackage('quiqqer/order-cancellation-policy')->getConfig();
        self::assertNotNull($Config);
        $Config->setValue('frontend', 'mailTo', 'revocation@example.test');
        $Config->setValue('frontend', 'useCaptcha', 0);
        $Config->save();

        self::assertSame('revocation@example.test', CancellationFormHelper::getMailRecipient());
        self::assertSame('revocation@example.test', CancellationFormHelper::getSetting('mailTo'));
        self::assertArrayHasKey('frontend', CancellationFormHelper::getSettings());
        self::assertFalse(CancellationFormHelper::isCaptchaEnabled());
        self::assertTrue(CancellationFormHelper::validateCaptcha(''));
        self::assertNull(CancellationFormHelper::getCaptchaDisplay());
        self::assertNotSame('', CancellationFormHelper::getIntroText());
        self::assertNotSame('', CancellationFormHelper::getSuccessText());
    }

    public function testUsesCaptchaWhenOptionalPackageIsAvailable(): void
    {
        $Config = QUI::getPackage('quiqqer/order-cancellation-policy')->getConfig();
        self::assertNotNull($Config);
        $Config->setValue('frontend', 'useCaptcha', 1);
        $Config->save();
        $PackageManager = QUI::getPackageManager();
        $Installed = new ReflectionProperty($PackageManager, 'installed');
        $originalInstalled = $Installed->getValue($PackageManager);
        $installed = $originalInstalled;
        $installed['quiqqer/captcha'] = true;

        try {
            $Installed->setValue($PackageManager, $installed);
            self::assertTrue(CancellationFormHelper::isCaptchaAvailable());
            self::assertFalse(CancellationFormHelper::validateCaptcha('phpunit-response'));
            self::assertInstanceOf(CaptchaDisplay::class, CancellationFormHelper::getCaptchaDisplay());
        } finally {
            $Installed->setValue($PackageManager, $originalInstalled);
        }
    }

    public function testResolvesProjectAndSitesFromAjaxContext(): void
    {
        $Project = QUI::getRewrite()->getProject();
        self::assertNotNull($Project);
        self::assertSame($Project, CancellationFormHelper::getProjectFromAjax(null));
        self::assertSame($Project->get(1)->getId(), CancellationFormHelper::getSiteFromAjax($Project, 1)?->getId());
        self::assertSame(
            QUI::getRewrite()->getSite()?->getId(),
            CancellationFormHelper::getSiteFromAjax(null, null)?->getId()
        );
        $CancellationSite = $this->createMock(Site::class);
        $CancellationSite->method('getId')->willReturn(81);
        $ProjectMock = $this->createMock(Project::class);
        $ProjectMock->method('getSites')->willReturn([$CancellationSite]);

        self::assertSame(
            $CancellationSite,
            CancellationFormHelper::getCancellationFormSite($ProjectMock)
        );
    }

    public function testReturnsSessionUserDataAndNobodyDefaults(): void
    {
        $Users = QUI::getUsers();
        $SessionUser = new ReflectionProperty($Users, 'Session');
        $SessionUser->setValue($Users, $Users->getSystemUser());
        $userData = CancellationFormHelper::getPrefilledUserData();

        self::assertSame(['firstName', 'lastName', 'email', 'phone'], array_keys($userData));

        $SessionUser->setValue($Users, $Users->getNobody());
        self::assertSame([
            'firstName' => '',
            'lastName' => '',
            'email' => '',
            'phone' => ''
        ], CancellationFormHelper::getPrefilledUserData());
    }
}

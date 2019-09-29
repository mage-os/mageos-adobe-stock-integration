<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\AdobeIms\Test\Unit\Model;

use Magento\AdobeIms\Model\LogOut;
use PHPUnit\Framework\{MockObject\MockObject, TestCase};
use Magento\AdobeImsApi\Api\Data\UserProfileInterface;
use Magento\AdobeImsApi\Api\UserProfileRepositoryInterface;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\Client\CurlFactory;
use Psr\Log\LoggerInterface;

/**
 * LogOut test.
 */
class LogOutTest extends TestCase
{
    /**
     * @var CurlFactory|MockObject $curlFactoryMock
     */
    private $curlFactoryMock;

    /**
     * @var LoggerInterface|MockObject $loggerInterfaceMock
     */
    private $loggerInterfaceMock;

    /**
     * @var UserContextInterface|MockObject $userContextInterfaceMock
     */
    private $userContextInterfaceMock;

    /**
     * @var ScopeConfigInterface|MockObject $scopeConfigInterfaceMock
     */
    private $scopeConfigInterfaceMock;

    /**
     * @var UserProfileRepositoryInterface|MockObject $userProfileRepositoryInterfaceMock
     */
    private $userProfileRepositoryInterfaceMock;

    /**
     * @var UserProfileInterface|MockObject $userProfileInterfaceMock
     */
    private $userProfileInterfaceMock;

    /**
     * @var LogOut|MockObject $model
     */
    private $model;

    /**
     * Successful result code.
     */
    const HTTP_FOUND = 302;

    /**
     * Error result code.
     */
    const HTTP_ERROR = 500;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->curlFactoryMock = $this->getMockBuilder(CurlFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->userProfileInterfaceMock = $this->createMock(UserProfileInterface::class);
        $this->userProfileRepositoryInterfaceMock = $this->createMock(UserProfileRepositoryInterface::class);
        $this->userContextInterfaceMock = $this->createMock(UserContextInterface::class);
        $this->scopeConfigInterfaceMock = $this->createMock(ScopeConfigInterface::class);
        $this->loggerInterfaceMock = $this->createMock(LoggerInterface::class);
        $this->model = new LogOut(
            $this->userContextInterfaceMock,
            $this->userProfileRepositoryInterfaceMock,
            $this->loggerInterfaceMock,
            $this->scopeConfigInterfaceMock,
            $this->curlFactoryMock
        );
    }

    /**
     * Test LogOut.
     */
    public function testExecute(): void
    {
        $this->userContextInterfaceMock->expects($this->exactly(1))
            ->method('getUserId')->willReturn(1);
        $this->userProfileRepositoryInterfaceMock->expects($this->exactly(1))
            ->method('getByUserId')
            ->willReturn($this->userProfileInterfaceMock);
        $curl = $this->createMock(\Magento\Framework\HTTP\Client\Curl::class);
        $this->curlFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($curl);
        $curl->expects($this->exactly(2))
            ->method('addHeader')
            ->willReturn(null);
        $curl->expects($this->once())
            ->method('get')
            ->willReturnSelf();
        $curl->expects($this->once())
            ->method('getStatus')
            ->willReturn(self::HTTP_FOUND);
        $this->userProfileInterfaceMock->expects($this->once())
            ->method('setAccessToken');
        $this->userProfileInterfaceMock->expects($this->once())
            ->method('setRefreshToken');
        $this->userProfileRepositoryInterfaceMock->expects($this->once())
            ->method('save')
            ->willReturn(null);
        $this->assertEquals(true, $this->model->execute());
    }

    /**
     * Test LogOut with Exception.
     */
    public function testExecuteWithException(): void
    {
        $this->userContextInterfaceMock->expects($this->exactly(1))
            ->method('getUserId')->willReturn(1);
        $this->userProfileRepositoryInterfaceMock->expects($this->exactly(1))
            ->method('getByUserId')
            ->willReturn($this->userProfileInterfaceMock);
        $curl = $this->createMock(\Magento\Framework\HTTP\Client\Curl::class);
        $this->curlFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($curl);
        $curl->expects($this->exactly(2))
            ->method('addHeader')
            ->willReturn(null);
        $curl->expects($this->once())
            ->method('get')
            ->willReturnSelf();
        $curl->expects($this->once())
            ->method('getStatus')
            ->willReturn(self::HTTP_ERROR);
        $this->loggerInterfaceMock->expects($this->once())
             ->method('critical');
        $this->assertEquals(false, $this->model->execute());
    }
}

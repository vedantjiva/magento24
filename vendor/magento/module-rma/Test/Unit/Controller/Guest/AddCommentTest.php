<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Unit\Controller\Guest;

use Magento\Framework\Controller\Result\Redirect;
use Magento\Rma\Model\Rma;
use Magento\Rma\Model\Rma\Status\History;
use Magento\Rma\Test\Unit\Controller\GuestTest;
use Magento\Sales\Model\Order;

class AddCommentTest extends GuestTest
{
    /**
     * @var string
     */
    protected $name = 'AddComment';

    public function testAddCommentAction()
    {
        $entityId = 7;
        $orderId = 5;
        $comment = 'comment';

        $this->request->expects($this->any())
            ->method('getParam')
            ->with('entity_id')
            ->willReturn($entityId);
        $this->request->expects($this->any())
            ->method('getPost')
            ->with('comment')
            ->willReturn($comment);

        $this->rmaHelper->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->salesGuestHelper->expects($this->once())
            ->method('loadValidOrder')
            ->with($this->request)
            ->willReturn(true);

        $rma = $this->createPartialMock(
            Rma::class,
            ['load', 'getCustomerId', 'getId', 'getOrderId']
        );
        $rma->expects($this->once())
            ->method('load')
            ->with($entityId)->willReturnSelf();
        $rma->expects($this->any())
            ->method('getId')
            ->willReturn($entityId);
        $rma->expects($this->any())
            ->method('getOrderId')
            ->willReturn($orderId);

        $order = $this->createPartialMock(
            Order::class,
            ['getCustomerId', 'load', 'getId']
        );
        $order->expects($this->any())
            ->method('getId')
            ->willReturn($orderId);

        $history = $this->createMock(History::class);
        $history->expects($this->once())
            ->method('sendCustomerCommentEmail');
        $history->expects($this->once())
            ->method('saveComment')
            ->with($comment, true, false);

        $this->objectManager->expects($this->at(0))
            ->method('create')
            ->with(Rma::class)
            ->willReturn($rma);
        $this->objectManager->expects($this->at(1))
            ->method('create')
            ->with(History::class)
            ->willReturn($history);

        $this->coreRegistry->expects($this->at(0))
            ->method('registry')
            ->with('current_order')
            ->willReturn($order);
        $this->coreRegistry->expects($this->at(1))
            ->method('registry')
            ->with('current_rma')
            ->willReturn($rma);

        $this->resultRedirect->expects($this->once())
            ->method('setPath')
            ->with('*/*/view', ['entity_id' => $entityId])
            ->willReturnSelf();

        $this->assertInstanceOf(Redirect::class, $this->controller->execute());
    }
}

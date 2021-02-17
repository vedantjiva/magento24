<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Unit\Controller\Adminhtml\Rma;

use Magento\Framework\Json\Helper\Data;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Layout;
use Magento\Framework\View\LayoutInterface;
use Magento\Rma\Model\Item;
use Magento\Rma\Test\Unit\Controller\Adminhtml\RmaTest;
use PHPUnit\Framework\MockObject\MockObject;

class LoadNewAttributesTest extends RmaTest
{
    protected $name = 'LoadNewAttributes';

    /**
     * @var Data|MockObject
     */
    protected $helperMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->helperMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testLoadNewAttributesActionWithoutUserAttributes()
    {
        $itemId = 2;
        $productId = 1;
        $rmaMock = $this->createMock(Item::class);
        $layoutMock = $this->getMockForAbstractClass(LayoutInterface::class);
        $blockMock = $this->getMockBuilder(Template::class)
            ->addMethods(['setProductId', 'initForm'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock->expects($this->at(0))
            ->method('getParam')
            ->with('product_id', null)
            ->willReturn($productId);
        $this->requestMock->expects($this->at(1))
            ->method('getParam')
            ->with('item_id')
            ->willReturn($itemId);
        $this->objectManagerMock->expects($this->once())
            ->method('create')
            ->with(Item::class, [])
            ->willReturn($rmaMock);
        $this->viewMock->expects($this->once())
            ->method('getLayout')
            ->willReturn($layoutMock);

        $layoutMock->expects($this->once())
            ->method('getBlock')
            ->with('magento_rma_edit_item')
            ->willReturn($blockMock);
        $blockMock->expects($this->once())
            ->method('setProductId')
            ->with($productId)->willReturnSelf();
        $blockMock->expects($this->once())
            ->method('initForm')->willReturnSelf();

        $this->responseMock->expects($this->never())
            ->method('setBody');

        $this->assertNull($this->action->execute());
    }

    public function testLoadNewAttributeActionResponseArray()
    {
        $itemId = 2;
        $productId = 1;
        $responseArray = ['html', 'html'];
        $responseString = 'json';
        $rmaMock = $this->createMock(Item::class);
        $layoutMock = $this->getMockForAbstractClass(LayoutInterface::class);
        $blockMock = $this->getMockBuilder(Template::class)
            ->addMethods(['setProductId', 'initForm'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock->expects($this->at(0))
            ->method('getParam')
            ->with('product_id', null)
            ->willReturn($productId);
        $this->requestMock->expects($this->at(1))
            ->method('getParam')
            ->with('item_id')
            ->willReturn($itemId);
        $this->objectManagerMock->expects($this->once())
            ->method('create')
            ->with(Item::class, [])
            ->willReturn($rmaMock);
        $this->viewMock->expects($this->once())
            ->method('getLayout')
            ->willReturn($layoutMock);

        $layoutMock->expects($this->once())
            ->method('getBlock')
            ->with('magento_rma_edit_item')
            ->willReturn($blockMock);
        $blockMock->expects($this->once())
            ->method('setProductId')
            ->with($productId)->willReturnSelf();

        $blockMock->expects($this->once())
            ->method('initForm')
            ->willReturn($this->formMock);

        $this->formMock->expects($this->once())
            ->method('hasNewAttributes')
            ->willReturn(true);
        $this->formMock->expects($this->once())
            ->method('toHtml')
            ->willReturn($responseArray);
        $this->objectManagerMock->expects($this->once())
            ->method('get')
            ->with(Data::class)
            ->willReturn($this->helperMock);
        $this->helperMock->expects($this->once())
            ->method('jsonEncode')
            ->with($responseArray)
            ->willReturn($responseString);
        $this->responseMock->expects($this->once())
            ->method('representJson')
            ->with($responseString);
        $this->responseMock->expects($this->never())
            ->method('setBody');
        $this->assertNull($this->action->execute());
    }

    public function testLoadNewAttributesActionResponseString()
    {
        $itemId = 2;
        $productId = 1;
        $responseString = 'json';
        $rmaMock = $this->createMock(Item::class);
        $layoutMock = $this->getMockForAbstractClass(LayoutInterface::class);
        $blockMock = $this->getMockBuilder(Template::class)
            ->addMethods(['setProductId', 'initForm'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock->expects($this->at(0))
            ->method('getParam')
            ->with('product_id', null)
            ->willReturn($productId);
        $this->requestMock->expects($this->at(1))
            ->method('getParam')
            ->with('item_id')
            ->willReturn($itemId);
        $this->objectManagerMock->expects($this->once())
            ->method('create')
            ->with(Item::class, [])
            ->willReturn($rmaMock);
        $this->viewMock->expects($this->once())
            ->method('getLayout')
            ->willReturn($layoutMock);

        $layoutMock->expects($this->once())
            ->method('getBlock')
            ->with('magento_rma_edit_item')
            ->willReturn($blockMock);
        $blockMock->expects($this->once())
            ->method('setProductId')
            ->with($productId)->willReturnSelf();

        $blockMock->expects($this->once())
            ->method('initForm')
            ->willReturn($this->formMock);

        $this->formMock->expects($this->once())
            ->method('hasNewAttributes')
            ->willReturn(true);
        $this->formMock->expects($this->once())
            ->method('toHtml')
            ->willReturn($responseString);
        $this->helperMock->expects($this->never())
            ->method('jsonEncode');
        $this->responseMock->expects($this->never())
            ->method('representJson');
        $this->responseMock->expects($this->once())
            ->method('setBody')
            ->with($responseString);
        $this->assertNull($this->action->execute());
    }

    public function testLoadNewAttributesAction()
    {
        $blockHtml = 'test';
        $productId = 1;
        $itemId = 2;
        $this->requestMock->expects($this->at(0))
            ->method('getParam')
            ->with('product_id')
            ->willReturn($productId);
        $this->requestMock->expects($this->at(1))
            ->method('getParam')
            ->with('item_id')
            ->willReturn($itemId);

        $rmaBlockMock = $this->getMockBuilder(\Magento\Rma\Block\Adminhtml\Rma\Edit\Item::class)
            ->disableOriginalConstructor()
            ->setMethods(['setProductId', 'setHtmlPrefixId', 'initForm', 'hasNewAttributes', 'toHtml'])
            ->getMock();

        $rmaBlockMock->expects($this->once())
            ->method('setProductId')
            ->with($productId)->willReturnSelf();
        $rmaBlockMock->expects($this->once())
            ->method('setHtmlPrefixId')
            ->with($itemId)->willReturnSelf();
        $rmaBlockMock->expects($this->once())
            ->method('initForm')->willReturnSelf();
        $rmaBlockMock->expects($this->once())
            ->method('hasNewAttributes')
            ->willReturn(true);
        $rmaBlockMock->expects($this->once())
            ->method('toHtml')
            ->willReturn($blockHtml);

        $layoutMock = $this->getMockBuilder(Layout::class)
            ->disableOriginalConstructor()
            ->getMock();
        $layoutMock->expects($this->once())
            ->method('getBlock')
            ->with('magento_rma_edit_item')
            ->willReturn($rmaBlockMock);

        $this->viewMock->expects($this->any())
            ->method('getLayout')
            ->willReturn($layoutMock);

        $this->assertNull($this->action->execute());
    }
}

<?php

namespace Cftf\AsnBundle\Tests\Controller;

Use Cftf\AsnBundle\Controller\DefaultController;
Use Cftf\AsnBundle\Service\AsnImport;
Use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
Use Symfony\Component\HttpFoundation\Request;

class DefaultControllerTest extends WebTestCase
{

    public function testImportAsnDoc()
    {
        $request = $this->createMock(Request::class);
        $container = $this->createMock("Symfony\Component\DependencyInjection\ContainerInterface");
        $service = $this->getMockBuilder(AsnImport::class)->disableOriginalConstructor()->getMock();

        $request = Request::create(
            '/cf/asn/import',
            'POST',
            array('fileUrl' => 'http://asn.jesandco.org/resources/D1000254')
        );

        $container->expects($this->once())
            ->method('get')
            ->with($this->equalTo('cftf_import.asn'))
            ->will($this->returnValue($service)
        );

        $controller = new DefaultController();
        $controller->setContainer($container);

        $result = $controller->importAsnAction($request);

        $this->assertEquals('{"message":"Framework imported successfully!"}', $result->getContent());
    }
}

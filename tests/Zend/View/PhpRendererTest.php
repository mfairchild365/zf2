<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_View
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

namespace ZendTest\View;

use Zend\View\PhpRenderer,
    Zend\View\TemplatePathStack,
    Zend\Filter\FilterChain;

/**
 * @category   Zend
 * @package    Zend_View
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @group      Zend_View
 */
class PhpRendererTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->renderer = new PhpRenderer();
    }

    public function testEngineIsIdenticalToRenderer()
    {
        $this->assertSame($this->renderer, $this->renderer->getEngine());
    }

    public function testUsesTemplatePathStackAsDefaultResolver()
    {
        $this->assertInstanceOf('Zend\View\TemplatePathStack', $this->renderer->resolver());
    }

    public function testCanSetResolverInstance()
    {
        $resolver = new TemplatePathStack();
        $this->renderer->setResolver($resolver);
        $this->assertSame($resolver, $this->renderer->resolver());
    }

    public function testPassingNameToResolverReturnsScriptName()
    {
        $this->renderer->resolver()->addPath(__DIR__ . '/_templates');
        $filename = $this->renderer->resolver('test.phtml');
        $this->assertEquals(realpath(__DIR__ . '/_templates/test.phtml'), $filename);
    }

    public function testUsesVariablesObjectForVarsByDefault()
    {
        $this->assertInstanceOf('Zend\View\Variables', $this->renderer->vars());
    }

    public function testCanSpecifyArrayAccessForVars()
    {
        $a = new \ArrayObject;
        $this->renderer->setVars($a);
        $this->assertSame($a, $this->renderer->vars());
    }

    public function testCanSpecifyArrayForVars()
    {
        $vars = array('foo' => 'bar');
        $this->renderer->setVars($vars);
        $this->assertEquals($vars, $this->renderer->vars());
    }

    public function testPassingArgumentToVarsReturnsValueFromThatKey()
    {
        $this->renderer->vars()->assign(array('foo' => 'bar'));
        $this->assertEquals('bar', $this->renderer->vars('foo'));
    }

    public function testUsesHelperBrokerByDefault()
    {
        $this->assertInstanceOf('Zend\View\HelperBroker', $this->renderer->getBroker());
    }

    public function testPassingArgumentToBrokerReturnsHelperByThatName()
    {
        $helper = $this->renderer->plugin('doctype');
        $this->assertInstanceOf('Zend\View\Helper\Doctype', $helper);
    }

    public function testPassingStringOfUndefinedClassToSetBrokerRaisesException()
    {
        $this->setExpectedException('Zend\View\Exception', 'Invalid');
        $this->renderer->setBroker('__foo__');
    }

    public function testPassingValidStringClassToSetBrokerCreatesBroker()
    {
        $this->renderer->setBroker('Zend\View\HelperBroker');
        $this->assertInstanceOf('Zend\View\HelperBroker', $this->renderer->getBroker());
    }

    public function invalidBrokers()
    {
        return array(
            array(true),
            array(1),
            array(1.0),
            array(array('foo')),
            array(new \stdClass),
        );
    }

    /**
     * @dataProvider invalidBrokers
     */
    public function testPassingInvalidArgumentToSetBrokerRaisesException($broker)
    {
        $this->setExpectedException('Zend\View\Exception', 'must extend');
        $this->renderer->setBroker($broker);
    }

    public function testInjectsSelfIntoHelperBroker()
    {
        $broker = $this->renderer->getBroker();
        $this->assertSame($this->renderer, $broker->getView());
    }

    public function testUsesFilterChainByDefault()
    {
        $this->assertInstanceOf('Zend\Filter\FilterChain', $this->renderer->getFilterChain());
    }

    public function testMaySetExplicitFilterChainInstance()
    {
        $filterChain = new FilterChain();
        $this->renderer->setFilterChain($filterChain);
        $this->assertSame($filterChain, $this->renderer->getFilterChain());
    }

    public function testRenderingAllowsVariableSubstitutions()
    {
        $expected = 'foo INJECT baz';
        $this->renderer->vars()->assign(array('bar' => 'INJECT'));
        $this->renderer->resolver()->addPath(__DIR__ . '/_templates');
        $test = $this->renderer->render('test.phtml');
        $this->assertContains($expected, $test);
    }

    public function testRenderingFiltersContentWithFilterChain()
    {
        $expected = 'foo bar baz';
        $this->renderer->getFilterChain()->attach(function($content) {
            return str_replace('INJECT', 'bar', $content);
        });
        $this->renderer->vars()->assign(array('bar' => 'INJECT'));
        $this->renderer->resolver()->addPath(__DIR__ . '/_templates');
        $test = $this->renderer->render('test.phtml');
        $this->assertContains($expected, $test);
    }

    public function testCanAccessHelpersInTemplates()
    {
        $this->renderer->resolver()->addPath(__DIR__ . '/_templates');
        $content = $this->renderer->render('test-with-helpers.phtml');
        foreach (array('foo', 'bar', 'baz') as $value) {
            $this->assertContains("<li>$value</li>", $content);
        }
    }
}

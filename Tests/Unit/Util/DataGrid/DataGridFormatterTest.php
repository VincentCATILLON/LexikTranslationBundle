<?php

namespace Lexik\Bundle\TranslationBundle\Tests\Unit\Util\DataGrid;

use Lexik\Bundle\TranslationBundle\Util\DataGrid\DataGridFormatter;
use Lexik\Bundle\TranslationBundle\Tests\Unit\BaseUnitTestCase;

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class DataGridFormatterTest extends BaseUnitTestCase
{
    /**
     * @group util
     */
    public function testCreateListResponse()
    {
        $datas = array(
            array('id' => 2, 'key' => 'key.say_goodbye', 'domain' => 'messages', 'client' => 'CanalTP', 'translations' => array(
                array('locale' => 'fr', 'content' => 'Au revoir'),
                array('locale' => 'en', 'content' => 'Goodbye'),
            )),
            array('id' => 1, 'key' => 'key.say_hello', 'domain' => 'superTranslations', 'client' => 'OtherCustom', 'translations' => array(
                array('locale' => 'fr', 'content' => 'Salut Other Custom'),
                array('locale' => 'en', 'content' => 'Hello Other Custom'),
                array('locale' => 'de', 'content' => 'Heil Other Custom'),
            )),
            array('id' => 3, 'key' => 'key.say_wtf', 'domain' => 'messages', 'client' => 'Custom', 'translations' => array(
                array('locale' => 'fr', 'content' => 'C\'est quoi ce bordel !?! Custom'),
                array('locale' => 'xx', 'content' => 'xxx xxx xxx'),
            )),
        );
        $total = 3;

        $expected = array(
            'translations' => array(
                array(
                    '_id' => 2,
                    '_domain' => 'messages',
                    '_key' => 'key.say_goodbye',
                    '_client' => 'CanalTP',
                    'de' => '',
                    'en' => 'Goodbye',
                    'fr' => 'Au revoir',
                ),
                array(
                    '_id' => 1,
                    '_domain' => 'superTranslations',
                    '_key' => 'key.say_hello',
                    '_client' => 'OtherCustom',
                    'de' => 'Heil Other Custom',
                    'en' => 'Hello Other Custom',
                    'fr' => 'Salut Other Custom',
                ),
                array(
                    '_id' => 3,
                    '_domain' => 'messages',
                    '_key' => 'key.say_wtf',
                    '_client' => 'Custom',
                    'de' => '',
                    'en' => '',
                    'fr' => 'C\'est quoi ce bordel !?! Custom'
                ),
            ),
            'total' => 3,
        );

        $formatter = new DataGridFormatter(array('de', 'en', 'fr'), 'orm'); 
        $this->assertEquals(json_encode($expected, JSON_HEX_APOS), $formatter->createListResponse($datas, $total)->getContent());
    }
}

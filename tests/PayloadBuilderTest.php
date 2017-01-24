<?php

/**
 * @license https://github.com/f500/swiftmailer-sparkpost/blob/master/LICENSE MIT
 */

namespace SwiftSparkPost\Tests;

use PHPUnit_Framework_TestCase;
use Swift_Attachment;
use Swift_Message;
use SwiftSparkPost\Message;
use SwiftSparkPost\PayloadBuilder;
use SwiftSparkPost\PayloadBuilderInterface;

/**
 * @copyright Future500 B.V.
 * @author    Jasper N. Brouwer <jasper@future500.nl>
 */
final class PayloadBuilderTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var PayloadBuilderInterface
     */
    private $payloadBuilder;

    protected function setUp()
    {
        $this->payloadBuilder = new PayloadBuilder();
    }

    protected function tearDown()
    {
        $this->payloadBuilder = null;
    }

    /**
     * @test
     */
    public function it_builds_the_payload_for_a_plain_swift_message()
    {
        $message = Swift_Message::newInstance()
                                ->setFrom('me@domain.com')
                                ->setTo(['john@doe.com'])
                                ->setSubject('Hello there!')
                                ->setBody('This is a special message just for you.', 'text/plain');

        $expectedPayload = [
            'recipients' => [
                ['address' => ['email' => 'john@doe.com']],
            ],
            'content'    => [
                'subject' => 'Hello there!',
                'from'    => 'me@domain.com',
                'text'    => 'This is a special message just for you.',
            ],
        ];

        $actualPayload = $this->payloadBuilder->buildPayload($message);

        $this->assertSame($expectedPayload, $actualPayload);
    }

    /**
     * @test
     */
    public function it_builds_the_same_payload_with_default_options_for_an_extended_message()
    {
        $message = Message::newInstance()
                          ->setFrom('me@domain.com')
                          ->setTo(['john@doe.com'])
                          ->setSubject('Hello there!')
                          ->setBody('This is a special message just for you.', 'text/plain');

        $expectedPayload = [
            'recipients' => [
                ['address' => ['email' => 'john@doe.com']],
            ],
            'content'    => [
                'subject' => 'Hello there!',
                'from'    => 'me@domain.com',
                'text'    => 'This is a special message just for you.',
            ],
            'options'    => [
                'transactional' => true,
                'inline_css'    => true,
            ],
        ];

        $actualPayload = $this->payloadBuilder->buildPayload($message);

        $this->assertSame($expectedPayload, $actualPayload);
    }

    /**
     * @test
     */
    public function it_builds_the_payload_for_a_full_message()
    {
        $message = Message::newInstance();
        $message->setFrom('me@domain.com', 'Me');
        $message->setReplyTo('noreply@domain.com', 'No Reply');
        $message->setTo(['john@doe.com' => 'John']);
        $message->setCc(['jane@doe.com' => 'Jane']);
        $message->setBcc(['jake@doe.com' => 'Jake']);
        $message->setSubject('Hello there!');
        $message->setBody('<html><body><p>This is a special message just for you.</p></body></html>', 'text/html');
        $message->addPart('This is a special message just for you.', 'text/plain');
        $message->getHeaders()->addTextHeader('X-Custom', 'some-custom-header');

        $attachment = Swift_Attachment::newInstance('Some text in a file.', 'textfile.txt', 'text/plain');
        $message->attach($attachment);

        $message->setCampaignId('some-campaign');
        $message->setPerRecipientTags('john@doe.com', ['eget', 'bibendum']);
        $message->setPerRecipientTags('jane@doe.com', ['nunc']);
        $message->setMetadata(['lorem' => 'ipsum', 'dolor' => 'sit', 'amet' => 'consectetur']);
        $message->setPerRecipientMetadata('john@doe.com', ['adipiscing' => 'elit', 'donec' => 'vitae']);
        $message->setPerRecipientMetadata('jane@doe.com', ['arcu' => 'non']);
        $message->setSubstitutionData(['aenean' => 'pretium', 'sapien' => 'nec', 'eros' => 'ullamcorper']);
        $message->setPerRecipientSubstitutionData('john@doe.com', ['rutrum' => 'sed', 'vel' => 'nunc']);
        $message->setPerRecipientSubstitutionData('jane@doe.com', ['mollis' => 'luctus']);
        $message->setOptions(
            [
                'open_tracking'    => false,
                'click_tracking'   => false,
                'transactional'    => false,
                'sandbox'          => true,
                'skip_suppression' => true,
                'inline_css'       => false,
                'ip_pool'          => 'some-ip-pool',
            ]
        );

        $expectedPayload = [
            'recipients'        => [
                [
                    'address'           => ['email' => 'john@doe.com', 'name' => 'John'],
                    'tags'              => ['eget', 'bibendum'],
                    'metadata'          => ['adipiscing' => 'elit', 'donec' => 'vitae'],
                    'substitution_data' => ['rutrum' => 'sed', 'vel' => 'nunc'],
                ],
                [
                    'address'           => ['email' => 'jane@doe.com', 'name' => 'Jane'],
                    'tags'              => ['nunc'],
                    'metadata'          => ['arcu' => 'non'],
                    'substitution_data' => ['mollis' => 'luctus'],
                ],
                [
                    'address' => ['email' => 'jake@doe.com', 'name' => 'Jake', 'header_to' => 'john@doe.com'],
                ],
            ],
            'content'           => [
                'subject'     => 'Hello there!',
                'from'        => ['email' => 'me@domain.com', 'name' => 'Me'],
                'reply_to'    => 'noreply@domain.com',
                'html'        => '<html><body><p>This is a special message just for you.</p></body></html>',
                'text'        => 'This is a special message just for you.',
                'headers'     => [
                    'X-Custom: some-custom-header',
                ],
                'attachments' => [
                    ['type' => 'text/plain', 'name' => 'textfile.txt', 'data' => 'U29tZSB0ZXh0IGluIGEgZmlsZS4='],
                ],
            ],
            'campaign_id'       => 'some-campaign',
            'metadata'          => ['lorem' => 'ipsum', 'dolor' => 'sit', 'amet' => 'consectetur'],
            'substitution_data' => ['aenean' => 'pretium', 'sapien' => 'nec', 'eros' => 'ullamcorper'],
            'options'           => [
                'open_tracking'    => false,
                'click_tracking'   => false,
                'transactional'    => false,
                'sandbox'          => true,
                'skip_suppression' => true,
                'inline_css'       => false,
                'ip_pool'          => 'some-ip-pool',
            ],
        ];

        $actualPayload = $this->payloadBuilder->buildPayload($message);

        $this->assertSame($expectedPayload, $actualPayload);
    }

    /**
     * @test
     */
    public function it_builds_the_payload_with_addresses_in_string_form()
    {
        $message = Message::newInstance()
                          ->setFrom('me@domain.com')
                          ->setReplyTo('noreply@domain.com')
                          ->setTo('john@doe.com')
                          ->setCc('jane@doe.com')
                          ->setBcc('jake@doe.com');

        $expectedPayload = [
            'recipients' => [
                ['address' => ['email' => 'john@doe.com']],
                ['address' => ['email' => 'jane@doe.com']],
                ['address' => ['email' => 'jake@doe.com', 'header_to' => 'john@doe.com']],
            ],
            'content'    => [
                'subject'  => '',
                'from'     => 'me@domain.com',
                'reply_to' => 'noreply@domain.com',
                'text'     => '',
            ],
            'options'    => ['transactional' => true, 'inline_css' => true],
        ];

        $actualPayload = $this->payloadBuilder->buildPayload($message);

        $this->assertSame($expectedPayload, $actualPayload);
    }

    /**
     * @test
     */
    public function it_builds_the_payload_with_addresses_and_names_in_string_form()
    {
        $message = Message::newInstance()
                          ->setFrom('me@domain.com', 'Me')
                          ->setReplyTo('noreply@domain.com', 'No Reply')
                          ->setTo('john@doe.com', 'John')
                          ->setCc('jane@doe.com', 'Jane')
                          ->setBcc('jake@doe.com', 'Jake');

        $expectedPayload = [
            'recipients' => [
                ['address' => ['email' => 'john@doe.com', 'name' => 'John']],
                ['address' => ['email' => 'jane@doe.com', 'name' => 'Jane']],
                ['address' => ['email' => 'jake@doe.com', 'name' => 'Jake', 'header_to' => 'john@doe.com']],
            ],
            'content'    => [
                'subject'  => '',
                'from'     => ['email' => 'me@domain.com', 'name' => 'Me'],
                'reply_to' => 'noreply@domain.com',
                'text'     => '',
            ],
            'options'    => ['transactional' => true, 'inline_css' => true],
        ];

        $actualPayload = $this->payloadBuilder->buildPayload($message);

        $this->assertSame($expectedPayload, $actualPayload);
    }

    /**
     * @test
     */
    public function it_builds_the_payload_with_addresses_in_array_form()
    {
        $message = Message::newInstance()
                          ->setFrom(['me@domain.com'])
                          ->setReplyTo(['noreply@domain.com'])
                          ->setTo(['john@doe.com'])
                          ->setCc(['jane@doe.com'])
                          ->setBcc(['jake@doe.com']);

        $expectedPayload = [
            'recipients' => [
                ['address' => ['email' => 'john@doe.com']],
                ['address' => ['email' => 'jane@doe.com']],
                ['address' => ['email' => 'jake@doe.com', 'header_to' => 'john@doe.com']],
            ],
            'content'    => [
                'subject'  => '',
                'from'     => 'me@domain.com',
                'reply_to' => 'noreply@domain.com',
                'text'     => '',
            ],
            'options'    => ['transactional' => true, 'inline_css' => true],
        ];

        $actualPayload = $this->payloadBuilder->buildPayload($message);

        $this->assertSame($expectedPayload, $actualPayload);
    }

    /**
     * @test
     */
    public function it_builds_the_payload_with_addresses_and_names_in_array_form()
    {
        $message = Message::newInstance()
                          ->setFrom(['me@domain.com' => 'Me'])
                          ->setReplyTo(['noreply@domain.com' => 'No Reply'])
                          ->setTo(['john@doe.com' => 'John'])
                          ->setCc(['jane@doe.com' => 'Jane'])
                          ->setBcc(['jake@doe.com' => 'Jake']);

        $expectedPayload = [
            'recipients' => [
                ['address' => ['email' => 'john@doe.com', 'name' => 'John']],
                ['address' => ['email' => 'jane@doe.com', 'name' => 'Jane']],
                ['address' => ['email' => 'jake@doe.com', 'name' => 'Jake', 'header_to' => 'john@doe.com']],
            ],
            'content'    => [
                'subject'  => '',
                'from'     => ['email' => 'me@domain.com', 'name' => 'Me'],
                'reply_to' => 'noreply@domain.com',
                'text'     => '',
            ],
            'options'    => ['transactional' => true, 'inline_css' => true],
        ];

        $actualPayload = $this->payloadBuilder->buildPayload($message);

        $this->assertSame($expectedPayload, $actualPayload);
    }

    /**
     * @test
     */
    public function it_convertss_asterisk_pipe_variables_to_curly_braces()
    {
        $message = Message::newInstance()
                          ->setFrom('me@domain.com')
                          ->setTo(['john@doe.com'])
                          ->setSubject('Hello there, *|NAME|*!')
                          ->setBody(
                              '<html><body><p>This is a special message just for you, *|NAME|*.</p></body></html>',
                              'text/html'
                          )
                          ->addPart('This is a special message just for you, *|NAME|*.', 'text/plain');

        $expectedPayload = [
            'recipients' => [
                ['address' => ['email' => 'john@doe.com']],
            ],
            'content'    => [
                'subject' => 'Hello there, {{NAME}}!',
                'from'    => 'me@domain.com',
                'html'    => '<html><body><p>This is a special message just for you, {{NAME}}.</p></body></html>',
                'text'    => 'This is a special message just for you, {{NAME}}.',
            ],
            'options'    => ['transactional' => true, 'inline_css' => true],
        ];

        $actualPayload = $this->payloadBuilder->buildPayload($message);

        $this->assertSame($expectedPayload, $actualPayload);
    }

    /**
     * @test
     * @expectedException \SwiftSparkPost\Exception
     * @expectedExceptionMessage Cannot send message without a recipient address
     */
    public function it_does_not_accept_a_message_without_real_recipients()
    {
        $message = Message::newInstance()
                          ->setFrom('me@domain.com');

        $this->payloadBuilder->buildPayload($message);
    }
}
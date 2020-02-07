<?php

return <<<'VALUE'
"namespace IPS\\Theme;\n\tfunction email_plaintext_core_notification_new_private_message( $message, $email ) {\n\t\t$return = '';\n\t\t$return .= <<<CONTENT\n\n\n\nCONTENT;\n$return .= htmlspecialchars( $email->language->addToStack(\"messenger_notify_title\", FALSE, array( 'sprintf' => array( $message->author()->name ) ) ), ENT_QUOTES | \\IPS\\HTMLENTITIES, 'UTF-8', FALSE );\n$return .= <<<CONTENT\n\n\n\nCONTENT;\n$return .= htmlspecialchars( $email->language->addToStack(\"email_url_to_message\", FALSE), ENT_QUOTES | \\IPS\\HTMLENTITIES, 'UTF-8', FALSE );\n$return .= <<<CONTENT\n: \nCONTENT;\n$return .= htmlspecialchars( $message->url(), ENT_QUOTES | \\IPS\\HTMLENTITIES, 'UTF-8', FALSE );\n$return .= <<<CONTENT\n\n\n-- \nCONTENT;\n\n$return .= \\IPS\\Settings::i()->board_name;\n$return .= <<<CONTENT\n\nCONTENT;\n\n\t\treturn $return;\n}"
VALUE;

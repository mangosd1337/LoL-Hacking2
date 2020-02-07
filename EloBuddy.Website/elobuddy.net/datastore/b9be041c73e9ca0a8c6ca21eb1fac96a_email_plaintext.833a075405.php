<?php

return <<<'VALUE'
"namespace IPS\\Theme;\n\tfunction email_plaintext_core_registration_complete( $member, $email ) {\n\t\t$return = '';\n\t\t$return .= <<<CONTENT\n\n\nCONTENT;\n$return .= htmlspecialchars( $email->language->addToStack(\"email_reg_complete\", FALSE, array( 'sprintf' => array( \\IPS\\Settings::i()->board_name ) ) ), ENT_QUOTES | \\IPS\\HTMLENTITIES, 'UTF-8', FALSE );\n$return .= <<<CONTENT\n\n\n\nCONTENT;\n$return .= htmlspecialchars( $email->language->addToStack(\"email_go_to_site\", FALSE, array( 'sprintf' => array( \\IPS\\Settings::i()->board_name ) ) ), ENT_QUOTES | \\IPS\\HTMLENTITIES, 'UTF-8', FALSE );\n$return .= <<<CONTENT\n: \nCONTENT;\n\n$return .= \\IPS\\Settings::i()->base_url;\n$return .= <<<CONTENT\n\n\n-- \nCONTENT;\n\n$return .= \\IPS\\Settings::i()->board_name;\n$return .= <<<CONTENT\n\nCONTENT;\n\n\t\treturn $return;\n}"
VALUE;

<?php

return <<<'VALUE'
"namespace IPS\\Theme;\n\tfunction email_plaintext_core_lost_password_init( $member, $vid, $email ) {\n\t\t$return = '';\n\t\t$return .= <<<CONTENT\n\n\nCONTENT;\n$return .= htmlspecialchars( $email->language->addToStack(\"email_lost_pass_plain\", FALSE), ENT_QUOTES | \\IPS\\HTMLENTITIES, 'UTF-8', FALSE );\n$return .= <<<CONTENT\n\n\n=====\n\nCONTENT;\n$return .= htmlspecialchars( $email->language->addToStack(\"email_reset_password\", FALSE), ENT_QUOTES | \\IPS\\HTMLENTITIES, 'UTF-8', FALSE );\n$return .= <<<CONTENT\n: \nCONTENT;\n\n$return .= \\IPS\\Http\\Url::internal( \"app=core&module=system&controller=lostpass&do=validate&vid={$vid}&mid={$member->member_id}\", \"front\", \"lost_password\", array(), 0 );\n$return .= <<<CONTENT\n\n=====\n\n-- \nCONTENT;\n\n$return .= \\IPS\\Settings::i()->board_name;\n$return .= <<<CONTENT\n\nCONTENT;\n\n\t\treturn $return;\n}"
VALUE;

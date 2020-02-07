//<?php

class hook1 extends _HOOK_CLASS_
{
	public function link( $warningRef=NULL, $groupFormatting=FALSE )
    {
		try
		{
			return parent::link( $warningRef, TRUE );
		}
		catch ( \RuntimeException $e )
		{
			if ( method_exists( get_parent_class(), __FUNCTION__ ) )
			{
				return call_user_func_array( 'parent::' . __FUNCTION__, func_get_args() );
			}
			else
			{
				throw $e;
			}
		}
    }
}
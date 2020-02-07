<?php
/**
 * @brief		Share Link Services
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		10 Jun 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\modules\admin\promotion;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Share Link Services
 */
class _sharelinks extends \IPS\Node\Controller
{	
	/**
	 * Node Class
	 */
	protected $nodeClass = '\IPS\core\ShareLinks\Service';
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'sharelinks_manage' );

		$reloadRoots	= FALSE;

		/* First, see if any are missing */
		$nodeClass = $this->nodeClass;

		foreach( $nodeClass::roots() as $node )
		{
			if( !file_exists( \IPS\ROOT_PATH . '/system/Content/ShareServices/' . ucwords( $node->key ) . '.php' ) )
			{
				$node->delete();
				$reloadRoots	= TRUE;
			}
		}

		/* Now see if there are any new classes */
		foreach( new \DirectoryIterator( \IPS\ROOT_PATH . '/system/Content/ShareServices/' ) as $file )
		{
			if( $file->isDir() OR $file->isDot() OR $file == 'index.html' )
			{
				continue;
			}

			$className	= str_replace( '.php', '', $file->getFilename() );

			try
			{
				$nodeClass::load( mb_strtolower( $className ), 'share_key' );
			}
			catch( \OutOfRangeException $e )
			{
				/* Class does not exist - let's add it */
				$newService	= new \IPS\core\ShareLinks\Service;
				$newService->key		= mb_strtolower( $className );
				$newService->groups		= '*';
				$newService->title		= $className;
				$newService->enabled	= 0;
				$newService->save();

				$reloadRoots	= TRUE;
			}
		}

		if( $reloadRoots === TRUE )
		{
			$nodeClass::resetRootResult();
		}

		return parent::execute();
	}

	/**
	 * Get Root Buttons
	 *
	 * @return	array
	 */
	public function _getRootButtons()
	{
		return array();
	}

	/**
	 * Delete
	 *
	 * @return	void
	 */
	public function delete()
	{
		/* Make sure the user confirmed the deletion */
		\IPS\Request::i()->confirmedDelete();

		\IPS\Db::i()->delete( 'core_share_links', array( 'share_id=?', (int) \IPS\Request::i()->id ) );

		/* Clear guest page caches */
		\IPS\Data\Cache::i()->clearAll();

		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=promotion&controller=sharelinks' ), 'saved' );
	}
}
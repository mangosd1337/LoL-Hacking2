<?php
/**
 * @brief		Abstract Search Query
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		21 Aug 2014
 * @version		SVN_VERSION_NUMBER
*/

namespace IPS\Content\Search;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Abstract Search Query
 */
abstract class _Query
{
	const TERM_OR_TAGS = 1;
	const TERM_AND_TAGS = 2;
	const TERM_OR_MODE  = 4;
	const TERM_TITLES_ONLY  = 8;
	const TAGS_MATCH_ITEMS_ONLY = 16;
	
	const HIDDEN_VISIBLE = 0;
	const HIDDEN_UNAPPROVED = 1;
	const HIDDEN_HIDDEN = -1;
	const HIDDEN_PARENT_HIDDEN = 2;
	
	const ORDER_NEWEST_UPDATED = 1;
	const ORDER_NEWEST_CREATED = 2;
	const ORDER_RELEVANCY = 3;
	const ORDER_OLDEST_UPDATED = 4;
	const ORDER_OLDEST_CREATED = 5;
	
	const SUPPORTS_JOIN_FILTERS = TRUE;
		
	/**
	 * Create new query
	 *
	 * @param	\IPS\Member	$member	The member performing the search (NULL for currently logged in member)
	 * @return	\IPS\Content\Search
	 */
	public static function init( \IPS\Member $member = NULL )
	{
		return new \IPS\Content\Search\Mysql\Query( $member ?: \IPS\Member::loggedIn() );
	}
		
	/**
	 * @brief	Number of results to get
	 */
	public $resultsToGet = 25;
	
	/**
	 * @brief	The member performing the search
	 */
	protected $member;
				
	/**
	 * Constructor
	 *
	 * @param	\IPS\Member	$member	The member performing the search
	 * @return	void
	 */
	public function __construct( \IPS\Member $member )
	{
		$this->member = $member;
		
		/* Exclude hidden items */
		if ( !$member->modPermission('can_view_hidden_content') )
		{
			$this->setHiddenFilter( static::HIDDEN_VISIBLE );
		}
		
		/* Exclude disabled applications */
		$filters = array();
		foreach ( \IPS\Application::enabledApplications() as $application )
		{
			foreach ( $application->extensions( 'core', 'ContentRouter' ) as $extension )
			{
				foreach ( $extension->classes as $class )
				{
					$filters[] = \IPS\Content\Search\ContentFilter::init( $class );
				}
			}
		}
		if ( !empty( $filters ) )
		{
			$this->filterByContent( $filters );
		}
	}
					
	/**
	 * Filter by multiple content types
	 *
	 * @param	array	$contentFilters	Array of \IPS\Content\Search\ContentFilter objects
	 * @param	bool	$type			TRUE means only include results matching the filters, FALSE means exclude all results matching the filters
	 * @return	\IPS\Content\Search\Query	(for daisy chaining)
	 */
	abstract public function filterByContent( array $contentFilters, $type = TRUE );
		
	/**
	 * Filter by author
	 *
	 * @param	\IPS\Member|int|array	$author						The author, or an array of author IDs
	 * @return	\IPS\Content\Search\Query	(for daisy chaining)
	 */
	abstract public function filterByAuthor( $author );
	
	/**
	 * Filter for profile
	 *
	 * @param	\IPS\Member	$member	The member whose profile is being viewed
	 * @return	\IPS\Content\Search\Query	(for daisy chaining)
	 */
	abstract public function filterForProfile( \IPS\Member $member );
	
	/**
	 * Filter by item author
	 *
	 * @param	\IPS\Member	$author		The author
	 * @return	\IPS\Content\Search\Query	(for daisy chaining)
	 */
	abstract public function filterByItemAuthor( \IPS\Member $author );
	
	/**
	 * Filter by content the user follows
	 *
	 * @param	bool	$includeContainers	Include content in containers the user follows?
	 * @param	bool	$includeItems		Include items and comments/reviews on items the user follows?
	 * @param	bool	$includeContainers	Include content posted by members the user follows?
	 * @return	\IPS\Content\Search\Query	(for daisy chaining)
	 */
	abstract public function filterByFollowed( $includeContainers, $includeItems, $includeMembers );
	
	/**
	 * Filter by content the user has posted in
	 *
	 * @return	\IPS\Content\Search\Query	(for daisy chaining)
	 */
	abstract public function filterByItemsIPostedIn();
	
	/**
	 * Filter by content the user has not read
	 *
	 * @note	If applicable, it is more efficient to call filterByContent() before calling this method
	 * @return	\IPS\Content\Search\Query	(for daisy chaining)
	 */
	abstract public function filterByUnread();
	
	/**
	 * Filter by start date
	 *
	 * @param	\IPS\DateTime|NULL	$start		The start date (only results AFTER this date will be returned)
	 * @param	\IPS\DateTime|NULL	$end		The end date (only results BEFORE this date will be returned)
	 * @return	\IPS\Content\Search\Query	(for daisy chaining)
	 */
	abstract public function filterByCreateDate( \IPS\DateTime $start = NULL, \IPS\DateTime $end = NULL );
	
	/**
	 * Filter by last updated date
	 *
	 * @param	\IPS\DateTime|NULL	$start		The start date (only results AFTER this date will be returned)
	 * @param	\IPS\DateTime|NULL	$end		The end date (only results BEFORE this date will be returned)
	 * @return	\IPS\Content\Search\Query	(for daisy chaining)
	 */
	abstract public function filterByLastUpdatedDate( \IPS\DateTime $start = NULL, \IPS\DateTime $end = NULL );
	
	/**
	 * Set hidden status
	 *
	 * @param	int|array|NULL	$statuses	The statuses (see HIDDEN_ constants) or NULL for any
	 * @return	\IPS\Content\Search\Query	(for daisy chaining)
	 */
	abstract public function setHiddenFilter( $statuses );
	
	/**
	 * Set limit
	 *
	 * @param	int		$limit	Number per page
	 * @return	\IPS\Content\Search\Query	(for daisy chaining)
	 */
	public function setLimit( $limit )
	{
		$this->resultsToGet = $limit;
		return $this;
	}
	
	/**
	 * Set page
	 *
	 * @param	int		$page	The page number
	 * @return	\IPS\Content\Search\Query	(for daisy chaining)
	 */
	abstract public function setPage( $page );
	
	/**
	 * Set order
	 *
	 * @param	int		$order	Order (see ORDER_ constants)
	 * @return	\IPS\Content\Search\Query	(for daisy chaining)
	 */
	abstract public function setOrder( $order );
	
	/**
	 * Permission Array
	 *
	 * @return	array
	 */
	public function permissionArray()
	{
		return $this->member->permissionArray();
	}
	
	/**
	 * Search
	 *
	 * @param	string|null	$term	The term to search for
	 * @param	array|null	$tags	The tags to search for
	 * @param	int			$method	\IPS\Content\Search\Index::i()->TERM_OR_TAGS or \IPS\Content\Search\Index::i()->TERM_AND_TAGS
	 * @return	\Traversable
	 */
	abstract public function search( $term = NULL, $tags = NULL, $method = 1 );
	
	/**
	 * Get count
	 *
	 * @param	string|null	$term	The term to search for
	 * @param	array|null	$tags	The tags to search for
	 * @param	int			$method	\IPS\Content\Search\Index::i()->TERM_OR_TAGS or \IPS\Content\Search\Index::i()->TERM_AND_TAGS
	 * @return	int
	 */
	public function count( $term = NULL, $tags = NULL, $method = 1 )
	{
		return $this->search( $term, $tags, $method )->count( TRUE );
	}
	
	/**
	 * Is this term a phrase?
	 *
	 * @param	string	$term	The term to search for
	 * @return	boolean
	 */
	public static function termIsPhrase( $term )
	{
		return (boolean) preg_match( '#^".*"$#', $term );
	}
	
	/**
	 * Convert the term into an array of words
	 *
	 * @param	string			$term			The term to search for
	 * @param	boolean			$ignorePhrase	When true, phrases are stripped of quotes and treated as normal words
	 * @param	int|NULL		$minLength		The minimum length a sequence of characters has to be before it is considered a word. If null, ft_min_word_len/innodb_ft_min_token_size is used.
	 * @param	int|NULL		$maxLength		The maximum length a sequence of characters can be for it to be considered a word. If null, ft_max_word_len/innodb_ft_max_token_size is used.
	 * @return	array
	 */
	public static function termAsWordsArray( $term, $ignorePhrase=FALSE, $minLength=NULL, $maxLength=NULL )
	{		
		/* If we haven't set a preferred min/max length, use the MySQL configuration */
		if ( $minLength === NULL or $maxLength === NULL )
		{
			/* If we don't already know what they are, get the values from the MySQL configuration */
			if ( ( $minLength === NULL and !isset( \IPS\Data\Store::i()->mysqlMinWord ) ) or ( $maxLength === NULL and !isset( \IPS\Data\Store::i()->mysqlMaxWord ) ) )
			{
				/* The variable we need depends on whether the table is MyISAM or InnoDB */
				$tableDefinition = \IPS\Db::i()->getTableDefinition('core_search_index');
				if ( $tableDefinition['engine'] == 'InnoDB' )
				{
					$minVariable = 'innodb_ft_min_token_size';
					$maxVariable = 'innodb_ft_max_token_size';
				}
				else
				{
					$minVariable = 'ft_min_word_len';
					$maxVariable = 'ft_max_word_len';
				}
				
				/* Now fetch those */			
				try
				{
					foreach ( new \IPS\Db\Select( 'SHOW VARIABLES WHERE Variable_Name=? OR Variable_Name=?', array( $minVariable, $maxVariable ), \IPS\Db::i() ) as $row )
					{
						if ( $row['Variable_name'] === $minVariable )
						{
							\IPS\Data\Store::i()->mysqlMinWord = intval( $row['Value'] );
						}
						elseif ( $row['Variable_name'] === $maxVariable )
						{
							\IPS\Data\Store::i()->mysqlMaxWord = intval( $row['Value'] );
						}
					}
				}
				catch( \IPS\Db\Exception $e ) { }
				
				/* If we weren't able to get them, set sensible defaults */
				if ( !isset( \IPS\Data\Store::i()->mysqlMinWord ) )
				{
					\IPS\Data\Store::i()->mysqlMinWord = 3;
				}
				if ( !isset( \IPS\Data\Store::i()->mysqlMaxWord ) )
				{
					\IPS\Data\Store::i()->mysqlMaxWord = 84;
				}
			}
			
			/* Set */
			if ( $minLength === NULL )
			{
				$minLength = \IPS\Data\Store::i()->mysqlMinWord;
			}
			if ( $maxLength === NULL )
			{
				$maxLength = \IPS\Data\Store::i()->mysqlMaxWord;
			}
		}
		
		/* Parse */
		$words = array();
		$currentWord = '';
		$inQuote = false;
		for ( $i = 0; $i < mb_strlen( $term ); $i++ )
		{
			$c = mb_substr( $term, $i, 1 );
			if ( $c == '"' )
			{
				if ( $ignorePhrase )
				{
					continue;
				}
				$inQuote = !$inQuote;
			}
			elseif ( $c == ' ' and !$inQuote )
			{
				$words[] = trim( $currentWord );
				$currentWord = '';
			}
			$currentWord .= $c;
		}
		$words[] = trim( $currentWord );
				
		/* Now check each of the words is acceptable */
		$finalWords = array();
		foreach( $words as $word )
		{
			if ( mb_strlen( $word ) >= $minLength and mb_strlen( $word ) <= $maxLength )
			{
				$finalWords[] = $word;
			}
		}
						
		/* And return */
		return $finalWords;
	}
}
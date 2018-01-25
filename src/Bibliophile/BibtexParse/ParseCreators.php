<?php

namespace Bibliophile\BibtexParse;

class ParseCreators
{
	function ParseCreators(){}
	/* Create writer arrays from bibtex input.
	'author field can be (delimiters between authors are 'and' or '&'):
	1. <first-tokens> <von-tokens> <last-tokens>
	2. <von-tokens> <last-tokens>, <first-tokens>
	3. <von-tokens> <last-tokens>, <jr-tokens>, <first-tokens>
	*/
	function parse($input)
	{
		$input = trim($input);
// split on ' and ' 
		$authorArray = preg_split("/\s(and|&)\s/i", $input);
		foreach($authorArray as $value)
		{
			$appellation = $prefix = $surname = $firstname = $initials = '';
			$this->prefix = array();
			$author = explode(",", preg_replace("/\s{2,}/", ' ', trim($value)));
			$size = sizeof($author);
// No commas therefore something like Mark Grimshaw, Mark Nicholas Grimshaw, M N Grimshaw, Mark N. Grimshaw
			if($size == 1)
			{
// Is complete surname enclosed in {...}, unless the string starts with a backslash (\) because then it is
// probably a special latex-sign.. 
// 2006.02.11 DR: in the last case, any NESTED curly braces should also be taken into account! so second 
// clause rules out things such as author="a{\"{o}}"
// 
                if(preg_match("/(.*){([^\\\].*)}/", $value, $matches) && 
					!(preg_match("/(.*){\\\.{.*}.*}/", $value, $matches2)))
				{
					$author = split(" ", $matches[1]);
					$surname = $matches[2];
				}
				else
				{
					$author = split(" ", $value);
// last of array is surname (no prefix if entered correctly)
					$surname = array_pop($author);
				}
			}
// Something like Grimshaw, Mark or Grimshaw, Mark Nicholas  or Grimshaw, M N or Grimshaw, Mark N.
			else if($size == 2)
			{
// first of array is surname (perhaps with prefix)
				list($surname, $prefix) = $this->grabSurname(array_shift($author));
			}
// If $size is 3, we're looking at something like Bush, Jr. III, George W
			else
			{
// middle of array is 'Jr.', 'IV' etc.
				$appellation = join(' ', array_splice($author, 1, 1));
// first of array is surname (perhaps with prefix)
				list($surname, $prefix) = $this->grabSurname(array_shift($author));
			}
			$remainder = join(" ", $author);
			list($firstname, $initials) = $this->grabFirstnameInitials($remainder);
			if(!empty($this->prefix))
				$prefix = join(' ', $this->prefix);
			$surname = $surname . ' ' . $appellation;
			$creators[] = array("$firstname", "$initials", "$surname", "$prefix");
		}
		if(isset($creators))
			return $creators;
		return FALSE;
	}
// grab firstname and initials which may be of form "A.B.C." or "A. B. C. " or " A B C " etc.
	function grabFirstnameInitials($remainder)
	{
		$firstname = $initials = '';
		$array = split(" ", $remainder);
		foreach($array as $value)
		{
			$firstChar = substr($value, 0, 1);
			if((ord($firstChar) >= 97) && (ord($firstChar) <= 122))
				$this->prefix[] = $value;
			else if(preg_match("/[a-zA-Z]{2,}/", trim($value)))
				$firstnameArray[] = trim($value);
			else
				$initialsArray[] = str_replace(".", " ", trim($value));
		}
		if(isset($initialsArray))
		{
			foreach($initialsArray as $initial)
				$initials .= ' ' . trim($initial);
		}
		if(isset($firstnameArray))
			$firstname = join(" ", $firstnameArray);
		return array($firstname, $initials);
	}
// surname may have title such as 'den', 'von', 'de la' etc. - characterised by first character lowercased.  Any 
// uppercased part means lowercased parts following are part of the surname (e.g. Van den Bussche)
	function grabSurname($input)
	{
		$surnameArray = split(" ", $input);
		$noPrefix = $surname = FALSE;
		foreach($surnameArray as $value)
		{
			$firstChar = substr($value, 0, 1);
			if(!$noPrefix && (ord($firstChar) >= 97) && (ord($firstChar) <= 122))
				$prefix[] = $value;
			else
			{
				$surname[] = $value;
				$noPrefix = TRUE;
			}
		}
		if($surname)
			$surname = join(" ", $surname);
		if(isset($prefix))
		{
			$prefix = join(" ", $prefix);
			return array($surname, $prefix);
		}
		return array($surname, FALSE);
	}
}
?>

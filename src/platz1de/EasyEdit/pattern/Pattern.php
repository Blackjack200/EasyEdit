<?php

namespace platz1de\EasyEdit\pattern;

use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\SafeSubChunkExplorer;
use pocketmine\block\Block;

class Pattern
{
	/**
	 * @var Pattern[]
	 */
	protected array $pieces;
	protected PatternArgumentData $args;

	/**
	 * Pattern constructor.
	 * @param Pattern[]                $pieces
	 * @param PatternArgumentData|null $args
	 */
	final public function __construct(array $pieces, ?PatternArgumentData $args = null)
	{
		$this->pieces = $pieces;
		$this->args = $args ?? new PatternArgumentData();
		$this->check();
	}

	public function check(): void
	{
	}

	/**
	 * @param int                  $x
	 * @param int                  $y
	 * @param int                  $z
	 * @param SafeSubChunkExplorer $iterator
	 * @param Selection            $selection
	 * @return Block|null
	 */
	public function getFor(int $x, int $y, int $z, SafeSubChunkExplorer $iterator, Selection $selection): ?Block
	{
		foreach ($this->pieces as $piece) {
			if ($piece->isValidAt($x, $y, $z, $iterator, $selection)) {
				return $piece->getFor($x, $y, $z, $iterator, $selection);
			}
		}
		return null;
	}

	/**
	 * @param int                  $x
	 * @param int                  $y
	 * @param int                  $z
	 * @param SafeSubChunkExplorer $iterator
	 * @param Selection            $selection
	 * @return bool
	 */
	public function isValidAt(int $x, int $y, int $z, SafeSubChunkExplorer $iterator, Selection $selection): bool
	{
		return true;
	}

	/**
	 * @return SelectionContext
	 */
	public function getSelectionContext(): SelectionContext
	{
		if (static::class === __CLASS__ && $this->pieces === []) {
			return SelectionContext::full(); //TODO: Add separate Mask pattern types
		}
		$context = SelectionContext::empty();
		$this->applySelectionContext($context);
		return $context;
	}

	/**
	 * @param SelectionContext $context
	 */
	public function applySelectionContext(SelectionContext $context): void
	{
		foreach ($this->pieces as $piece) {
			$piece->applySelectionContext($context);
		}
	}

	/**
	 * @return string
	 */
	public function fastSerialize(): string
	{
		$stream = new ExtendedBinaryStream();
		$stream->putString($this->args->fastSerialize());
		$stream->putInt(count($this->pieces));
		foreach ($this->pieces as $piece) {
			$stream->putString($piece->fastSerialize());
		}
		$stream->putString(static::class);
		return $stream->getBuffer();
	}

	/**
	 * @param string $data
	 * @return Pattern
	 */
	public static function fastDeserialize(string $data): Pattern
	{
		$stream = new ExtendedBinaryStream($data);
		$args = PatternArgumentData::fastDeserialize($stream->getString());
		$pieces = [];
		for ($i = $stream->getInt(); $i > 0; $i--) {
			$pieces[] = self::fastDeserialize($stream->getString());
		}
		$type = $stream->getString();
		return new $type($pieces, $args);
	}
}
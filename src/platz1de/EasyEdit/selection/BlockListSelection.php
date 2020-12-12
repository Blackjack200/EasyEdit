<?php

namespace platz1de\EasyEdit\selection;

use platz1de\EasyEdit\task\ReferencedChunkManager;
use pocketmine\level\format\Chunk;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\level\utils\SubChunkIteratorManager;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\tile\Tile;

abstract class BlockListSelection extends Selection
{
	/**
	 * @var ReferencedChunkManager
	 */
	private $manager;
	/**
	 * @var SubChunkIteratorManager
	 */
	private $iterator;
	/**
	 * @var int
	 */
	private $xSize;
	/**
	 * @var int
	 */
	private $zSize;
	/**
	 * @var int
	 */
	private $ySize;
	/**
	 * @var CompoundTag[]
	 */
	private $tiles = [];

	public function __construct(string $player, ReferencedChunkManager $manager, int $xSize, int $ySize, int $zSize)
	{
		parent::__construct($player);
		$this->manager = $manager;
		$this->iterator = new SubChunkIteratorManager($manager);
		$this->xSize = $xSize;
		$this->ySize = $ySize;
		$this->zSize = $zSize;
	}

	/**
	 * @return ReferencedChunkManager
	 */
	public function getManager(): ReferencedChunkManager
	{
		return $this->manager;
	}

	/**
	 * @param Position $place
	 * @return Chunk[]
	 */
	public function getNeededChunks(Position $place): array
	{
		$chunks = [];
		for ($x = $place->getX() >> 4; $x <= ($place->getX() + $this->xSize) >> 4; $x++) {
			for ($z = $place->getZ() >> 4; $z <= ($place->getZ() + $this->zSize) >> 4; $z++) {
				$place->getLevelNonNull()->loadChunk($x, $z);
				$chunks[] = $place->getLevelNonNull()->getChunk($x, $z);
			}
		}
		return $chunks;
	}

	/**
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 * @param int $id
	 * @param int $damage
	 */
	public function addBlock(int $x, int $y, int $z, int $id, int $damage): void
	{
		if($id === 0){
			$id = 217; //structure_void
		}
		$this->iterator->moveTo($x, $y, $z);
		$this->iterator->currentSubChunk->setBlock($x & 0x0f, $y & 0x0f, $z & 0x0f, $id, $damage);
	}

	/**
	 * @return int
	 */
	public function getXSize(): int
	{
		return $this->xSize;
	}

	/**
	 * @return int
	 */
	public function getYSize(): int
	{
		return $this->ySize;
	}

	/**
	 * @return int
	 */
	public function getZSize(): int
	{
		return $this->zSize;
	}

	/**
	 * @return SubChunkIteratorManager
	 */
	public function getIterator(): SubChunkIteratorManager
	{
		return $this->iterator;
	}

	/**
	 * @param CompoundTag $tile
	 */
	public function addTile(CompoundTag $tile): void
	{
		$this->tiles[Level::blockHash($tile->getInt(Tile::TAG_X), $tile->getInt(Tile::TAG_Y), $tile->getInt(Tile::TAG_Z))] = $tile;
	}

	/**
	 * @return CompoundTag[]
	 */
	public function getTiles(): array
	{
		return $this->tiles;
	}

	/**
	 * @return string
	 */
	public function serialize(): string
	{
		return igbinary_serialize([
			"player" => $this->player,
			"xSize" => $this->xSize,
			"ySize" => $this->ySize,
			"zSize" => $this->zSize,
			"chunks" => array_map(static function (Chunk $chunk) {
				return $chunk->fastSerialize();
			}, $this->getManager()->getChunks()),
			"level" => $this->getManager()->getLevelName(),
			"tiles" => $this->getTiles()
		]);
	}

	/**
	 * @param string $serialized
	 * @noinspection PhpMissingParamTypeInspection
	 */
	public function unserialize($serialized): void
	{
		$data = igbinary_unserialize($serialized);
		$this->player = $data["player"];
		$this->xSize = $data["xSize"];
		$this->ySize = $data["ySize"];
		$this->zSize = $data["zSize"];
		$this->manager = new ReferencedChunkManager($data["level"]);
		foreach ($data["chunks"] as $chunk) {
			/** @var Chunk $chunk */
			$chunk = Chunk::fastDeserialize($chunk);
			$this->manager->setChunk($chunk->getX(), $chunk->getZ(), $chunk);
		}
		$this->iterator = new SubChunkIteratorManager($this->manager);
		$this->tiles = $data["tiles"];
	}
}
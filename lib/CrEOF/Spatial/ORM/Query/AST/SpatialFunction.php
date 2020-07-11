<?php


namespace CrEOF\Spatial\ORM\Query\AST;


use CrEOF\Spatial\Exception\UnsupportedPlatformException;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\Query\AST\ASTException;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query\SqlWalker;

class SpatialFunction extends FunctionNode
{
    public const PLATFORM_MYSQL = 'mysql';
    public const PLATFORM_PGSQL = 'postgresql';

    public const FUNCTION_LIST = [
        'geometry' => ['platforms' => [self::PLATFORM_PGSQL], 'min' => 1, 'max' => 1],
        'point' => ['platforms' => [self::PLATFORM_MYSQL], 'min' => 2, 'max' => 2],
        'linestring' => ['platforms' => [self::PLATFORM_MYSQL], 'min' => 2, 'max' => 2],
        'mbrcontains' => ['platforms' => [self::PLATFORM_MYSQL], 'min' => 2, 'max' => 2, 'numeric' => true],
        'mbrdisjoint' => ['platforms' => [self::PLATFORM_MYSQL], 'numeric' => true],
        'mbrequals' => ['platforms' => [self::PLATFORM_MYSQL], 'min' => 2, 'max' => 2],
        'mbrintersects' => ['platforms' => [self::PLATFORM_MYSQL], 'min' => 2, 'max' => 2],
        'mbroverlaps' => ['platforms' => [self::PLATFORM_MYSQL], 'min' => 2, 'max' => 2],
        'mbrtouches' => ['platforms' => [self::PLATFORM_MYSQL], 'min' => 2, 'max' => 2],
        'mbrwithin' => ['platforms' => [self::PLATFORM_MYSQL], 'min' => 2, 'max' => 2],
        'st_area' => ['platforms' => [self::PLATFORM_MYSQL, self::PLATFORM_PGSQL], 'min' => 1, 'max' => 1, 'numeric' => true],
        'st_asbinary' => ['platforms' => [self::PLATFORM_MYSQL, self::PLATFORM_PGSQL], 'min' => 1, 'max' => 1,
            'geometry' => true],
        'st_asgeojson' => ['platforms' => [self::PLATFORM_MYSQL => ['max' => 3],
            self::PLATFORM_PGSQL => ['max' => 4]], 'min' => 1, 'geometry' => true],
        'st_astext' => ['platforms' => [self::PLATFORM_MYSQL, self::PLATFORM_PGSQL], 'min' => 1, 'max' => 1,
            'geometry' => true],
        'st_azimuth' => ['platforms' => [self::PLATFORM_PGSQL], 'min' => 2, 'max' => 2],
        'st_boundary' => ['platforms' => [self::PLATFORM_PGSQL], 'min' => 1, 'max' => 1, 'geometry' => true],
        'st_buffer' => ['platforms' => [self::PLATFORM_MYSQL => ['max' => 5], self::PLATFORM_PGSQL => ['max' => 3]],
            'min' => 2, 'numeric' => true],
        'st_centroid' => ['platforms' => [self::PLATFORM_MYSQL, self::PLATFORM_PGSQL], 'min' => 1, 'max' => 1],
        'st_closestpoint' => ['platforms' => [self::PLATFORM_PGSQL], 'min' => 2, 'max' => 2],
        'st_collect' => ['platforms' => [self::PLATFORM_PGSQL], 'min' => 1, 'max' => 2],
        'st_contains' => ['platforms' => [self::PLATFORM_MYSQL, self::PLATFORM_PGSQL], 'min' => 2, 'max' => 2,
            'numeric' => true],
        'st_containsproperly' => ['platforms' => [self::PLATFORM_PGSQL], 'min' => 2, 'max' => 2, 'numeric' => true],
        'st_coveredby' => ['platforms' => [self::PLATFORM_PGSQL], 'min' => 2, 'max' => 2, 'numeric' => true],
        'st_covers' => ['platforms' => [self::PLATFORM_PGSQL], 'min' => 2, 'max' => 2, 'numeric' => true],
        'st_crosses' => ['platforms' => [self::PLATFORM_MYSQL, self::PLATFORM_PGSQL], 'min' => 2, 'max' => 2,
            'numeric' => true],
        'st_difference' => ['platforms' => [self::PLATFORM_PGSQL, self::PLATFORM_MYSQL], 'min' => 2, 'max' => 2,
            'geometry' => true],
        'st_dimension' => ['platforms' => [self::PLATFORM_MYSQL, self::PLATFORM_PGSQL], 'min' => 1, 'max' => 1],
        'st_disjoint' => ['platforms' => [self::PLATFORM_MYSQL, self::PLATFORM_PGSQL], 'min' => 2, 'max' => 2,
            'numeric' => true],
        'st_distance' => ['platforms' => [self::PLATFORM_MYSQL => ['max' => 2], self::PLATFORM_PGSQL => ['max' => 3]],
            'min' => 2, 'numeric' => true],
        'st_distancesphere' => ['platforms' => [self::PLATFORM_PGSQL], 'min' => 2, 'max' => 2, 'numeric' => true],
        'st_distance_sphere' => ['platforms' => [self::PLATFORM_MYSQL], 'min' => 2, 'max' => 2, 'numeric' => true],
        'st_dwithin' => ['platforms' => [self::PLATFORM_PGSQL], 'min' => 2, 'max' => 3],
        'st_endpoint' => ['platforms' => [self::PLATFORM_MYSQL, self::PLATFORM_PGSQL], 'min' => 1, 'max' => 1],
        'st_envelope' => ['platforms' => [self::PLATFORM_MYSQL, self::PLATFORM_PGSQL], 'min' => 1, 'max' => 1],
        'st_equals' => ['platforms' => [self::PLATFORM_MYSQL, self::PLATFORM_PGSQL], 'min' => 2, 'max' => 2],
        'st_exteriorring' => ['platforms' => [self::PLATFORM_MYSQL, self::PLATFORM_PGSQL], 'min' => 1, 'max' => 1],
        'st_expand' => ['platforms' => [self::PLATFORM_PGSQL], 'min' => 2, 'max' => 2],
        'st_extent' => ['platforms' => [self::PLATFORM_PGSQL], 'min' => 1, 'max' => 1],
        'st_geometrytype' => ['platforms' => [self::PLATFORM_MYSQL, self::PLATFORM_PGSQL], 'min' => 1, 'max' => 1],
        'st_geographyfromtext' => ['platforms' => [self::PLATFORM_PGSQL], 'min' => 1, 'max' => 2],
        'st_geometryn' => ['platforms' => [self::PLATFORM_PGSQL, self::PLATFORM_MYSQL], 'min' => 2, 'max' => 2,
            'geometry' => true],
        'st_geomfromewkt' => ['platforms' => [self::PLATFORM_PGSQL], 'min' => 1, 'max' => 2],
        'st_geomfromtext' => ['platforms' => [self::PLATFORM_MYSQL => ['max' => 1],
            self::PLATFORM_PGSQL => ['max' => 2]], 'min' => 1],
        'st_interiorringn' => ['platforms' => [self::PLATFORM_MYSQL, self::PLATFORM_PGSQL], 'min' => 2, 'max' => 2],
        'st_intersection' => ['platforms' => [self::PLATFORM_PGSQL, self::PLATFORM_MYSQL], 'min' => 2, 'max' => 2],
        'st_intersects' => ['platforms' => [self::PLATFORM_MYSQL, self::PLATFORM_PGSQL], 'min' => 2, 'max' => 2],
        'st_isclosed' => ['platforms' => [self::PLATFORM_MYSQL, self::PLATFORM_PGSQL], 'min' => 1, 'max' => 1],
        'st_isempty' => ['platforms' => [self::PLATFORM_MYSQL, self::PLATFORM_PGSQL], 'min' => 1, 'max' => 1],
        'st_issimple' => ['platforms' => [self::PLATFORM_MYSQL, self::PLATFORM_PGSQL], 'min' => 1, 'max' => 1],
        'st_length' => ['platforms' => [self::PLATFORM_MYSQL, self::PLATFORM_PGSQL], 'min' => 1, 'max' => 1,
            'numeric' => true],
        'st_linestringfromwkb' => ['platforms' => [self::PLATFORM_MYSQL => ['max' => 3],
            self::PLATFORM_PGSQL => ['max' => 2]], 'min' => 1],
        'st_numinteriorrings' => ['platforms' => [self::PLATFORM_MYSQL, self::PLATFORM_PGSQL], 'min' => 1, 'max' => 1],
        'st_numpoints' => ['platforms' => [self::PLATFORM_MYSQL, self::PLATFORM_PGSQL], 'min' => 1, 'max' => 1],
        'st_overlaps' => ['platforms' => [self::PLATFORM_MYSQL, self::PLATFORM_PGSQL], 'min' => 2, 'max' => 2],
        'st_pointfromwkb' => ['platforms' => [self::PLATFORM_MYSQL => ['max' => 3],
            self::PLATFORM_PGSQL => ['max' => 2]], 'min' => 1],
        'st_perimeter' => ['platforms' => [self::PLATFORM_PGSQL], 'min' => 1, 'max' => 1],
        'st_point' => 'st_makepoint',
        'st_linecrossingdirection' => ['platforms' => [self::PLATFORM_PGSQL], 'min' => 2, 'max' => 2, 'numeric' => true],
        'st_lineinterpolatepoint' => ['platforms' => [self::PLATFORM_PGSQL], 'min' => 2, 'max' => 2],
        'st_linelocatepoint' => ['platforms' => [self::PLATFORM_PGSQL], 'min' => 2, 'max' => 2],
        'st_linesubstring' => ['platforms' => [self::PLATFORM_PGSQL], 'min' => 3, 'max' => 3, 'geometry' => true],
        'st_makebox2d' => ['platforms' => [self::PLATFORM_PGSQL], 'min' => 2, 'max' => 2],
        'st_makeenvelope' => ['platforms' => [self::PLATFORM_MYSQL => ['min' => 2, 'max' => 2],
            self::PLATFORM_PGSQL => ['min' => 4, 'max' => 5]]],
        'st_makeline' => ['platforms' => [self::PLATFORM_PGSQL], 'min' => 2, 'max' => 2],
        'st_makepoint' => ['platforms' => [self::PLATFORM_PGSQL], 'min' => 2, 'max' => 4],
        'st_pointn' => ['platforms' => [self::PLATFORM_MYSQL => ['max' => 1], self::PLATFORM_PGSQL => ['max' => 2]], 'min' => 1],
        'st_simplify' => ['platforms' => [self::PLATFORM_PGSQL], 'min' => 2, 'max' => 2],
        'st_split' => ['platforms' => [self::PLATFORM_PGSQL], 'min' => 2, 'max' => 2, 'geometry' => true],
        'st_snaptogrid' => ['platforms' => [self::PLATFORM_PGSQL], 'min' => 2, 'max' => 6, 'geometry' => true],
        'st_scale' => ['platforms' => [self::PLATFORM_PGSQL], 'min' => 3, 'max' => 3],
        'st_srid' => ['platforms' => [self::PLATFORM_MYSQL, self::PLATFORM_PGSQL], 'min' => 1, 'max' => 1],
        'st_setsrid' => ['platforms' => [self::PLATFORM_PGSQL], 'min' => 2, 'max' => 2],
        'st_startpoint' => ['platforms' => [self::PLATFORM_MYSQL, self::PLATFORM_PGSQL], 'min' => 1, 'max' => 1],
        'st_summary' => ['platforms' => [self::PLATFORM_PGSQL], 'min' => 1, 'max' => 1],
        'st_touches' => ['platforms' => [self::PLATFORM_MYSQL, self::PLATFORM_PGSQL], 'min' => 2, 'max' => 2],
        'st_transform' => ['platforms' => [self::PLATFORM_PGSQL, self::PLATFORM_PGSQL], 'min' => 2, 'max' => 2],
        'st_translate' => ['platforms' => [self::PLATFORM_PGSQL], 'min' => 3, 'max' => 4, 'geometry' => true],
        'st_union' => ['platforms' => [self::PLATFORM_MYSQL => ['min' => 2], self::PLATFORM_PGSQL => ['min' => 1]],
            'max' => 2, 'geometry' => true],
        'st_within' => ['platforms' => [self::PLATFORM_MYSQL, self::PLATFORM_PGSQL], 'min' => 2, 'max' => 2],
        'st_x' => ['platforms' => [self::PLATFORM_MYSQL, self::PLATFORM_PGSQL], 'min' => 1, 'max' => 1],
        'st_y' => ['platforms' => [self::PLATFORM_MYSQL, self::PLATFORM_PGSQL], 'min' => 1, 'max' => 1]
    ];

    /**
     * @var bool
     */
    protected $isReturnGeometry = false;

    /**
     * @var array
     */
    protected $platforms = [];

    /**
     * @var Node[]
     */
    protected $geomExpr = [];

    /**
     * @var int
     */
    protected $minGeomExpr;

    /**
     * @var int
     */
    protected $maxGeomExpr;

    public function __construct($name)
    {
        parent::__construct($name);

        $targetName = strtolower($name);

        if (!array_key_exists($targetName, self::FUNCTION_LIST)) {
            return;
        }

        $target = self::FUNCTION_LIST[$targetName];

        if (is_string($target)) {
            $target = self::FUNCTION_LIST[$target];
        }

        if (!empty($target['min'])) {
            $this->minGeomExpr = $target['min'];
        }

        if (!empty($target['max'])) {
            $this->maxGeomExpr = $target['max'];
        }

        if (!empty($target['geometry'])) {
            $this->isReturnGeometry = true;
        }

        foreach ($target['platforms'] as $key => $value) {
            if (is_array($value)) {
                $this->platforms[] = $key;
            } else {
                $this->platforms[] = $value;
            }
        }

    }

    /**
     * @param Parser $parser
     * @throws QueryException
     */
    public function parse(Parser $parser)
    {
        $lexer = $parser->getLexer();

        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->geomExpr[] = $parser->ArithmeticPrimary();

        while (count($this->geomExpr) < $this->minGeomExpr || (($this->maxGeomExpr === null || count($this->geomExpr) < $this->maxGeomExpr) && $lexer->lookahead['type'] != Lexer::T_CLOSE_PARENTHESIS)) {
            $parser->match(Lexer::T_COMMA);

            $this->geomExpr[] = $parser->ArithmeticPrimary();
        }

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    /**
     * @param SqlWalker $sqlWalker
     *
     * @return string
     *
     * @throws UnsupportedPlatformException
     * @throws DBALException
     * @throws ASTException
     */
    public function getSql(SqlWalker $sqlWalker)
    {
        $this->validatePlatform($sqlWalker->getConnection()->getDatabasePlatform());

        $arguments = array();
        foreach ($this->geomExpr as $expression) {
            $arguments[] = $expression->dispatch($sqlWalker);
        }

        return sprintf('%s(%s)', $this->name, implode(', ', $arguments));
    }

    /**
     * @param AbstractPlatform $platform
     *
     * @throws UnsupportedPlatformException
     */
    protected function validatePlatform(AbstractPlatform $platform)
    {
        $platformName = $platform->getName();

        if (isset($this->platforms) && !in_array($platformName, $this->platforms)) {
            throw new UnsupportedPlatformException(
                sprintf('DBAL platform "%s" is not currently supported.', $platformName)
            );
        }
    }


    public function isReturnGeometry(): bool
    {
        return $this->isReturnGeometry;
    }

}
<?php

declare(strict_types=1);

namespace Thesis\Protoc\Plugin\Generator;

use Nette\PhpGenerator\PhpNamespace;

/**
 * The names a generated message file refers to the runtime thesis/protobuf with: `Reflection\Field`
 * through `use Thesis\Protobuf\Reflection;`, `Protobuf\Map` through `use Thesis\Protobuf;`. The only place
 * these names are decided: attributes, reflection types, the map type, its default and its docblock all take them from here.
 *
 * The import is added on the first reference, and the name is then asked from Nette, which prints the import:
 * when a class of the namespace already owns the short name (a message named Reflection or Protobuf, compared
 * case-insensitively), Nette imports the runtime under another alias (Reflection1), and the reference follows it.
 * The file is printed without type resolving (see FileFactory), so nothing else would rewrite the reference.
 *
 * Create it only after the classes of the namespace are added: PhpNamespace::add() refuses a class whose name is
 * already an alias, while addUse() steps aside for a class added before.
 *
 * @api
 */
final readonly class RuntimeImports
{
    private const string PROTOBUF = 'Thesis\Protobuf';
    private const string REFLECTION = 'Thesis\Protobuf\Reflection';

    public function __construct(
        private PhpNamespace $namespace,
    ) {}

    /**
     * @param non-empty-string $name a class of Thesis\Protobuf\Reflection, optionally followed by a member: "Field", "StringT::T"
     */
    public function reflection(string $name): string
    {
        return $this->refer(self::REFLECTION, $name);
    }

    /**
     * @param non-empty-string $name a class of Thesis\Protobuf: "Map"
     */
    public function protobuf(string $name): string
    {
        return $this->refer(self::PROTOBUF, $name);
    }

    /**
     * @param non-empty-string $import
     * @param non-empty-string $name
     */
    private function refer(string $import, string $name): string
    {
        $this->namespace->addUse($import);

        $separator = strpos($name, '::');
        $class = $separator === false ? $name : substr($name, 0, $separator);
        $member = $separator === false ? '' : substr($name, $separator);

        return $this->namespace->simplifyName("{$import}\\{$class}") . $member;
    }
}

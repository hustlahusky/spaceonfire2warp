# spaceonfire2warp

Console tool which helps you to move from [`spaceonfire`][spaceonfire] libraries to [`getwarp`][getwarp] ones.
Written in PHP 8.1, process source code in parallel.

## Installation

Pull Docker image from GitHub:

```bash
docker pull ghcr.io/hustlahusky/spaceonfire2warp:latest
```

## Usage

Run a Docker container with your project source code mounted to `/app`.

```bash
docker run --rm -t -v $PWD:/app ghcr.io/hustlahusky/spaceonfire2warp
```

> **WARNING**: script will replace the content of your source code files!
> Make sure that you've committed all your changes to VCS, or you've done a backup.

You can specify a relative directory to find files in. If you didn't, script would try to find it automatically
from `composer.json`:

1. value from `extra.spaceonfire2warp.directory`
2. first path from `autoload.psr-4`
3. working directory

> **Important notice about composer.json**. The tool will include your composer autoloader in order to locate symbols
> from your codebase, but not to execute them. Composer autoloader by default runs platform checks that can fail inside
> our container. In this case try to disable it and dump fresh autoloader.
>
> More info about [platform-check](https://getcomposer.org/doc/06-config.md#platform-check)

You also can specify custom filename pattern with `--pattern` option (which is `/.php$/` by default) and choose target
version to update to:

- `--v3`: update vendor namespace and perform known code modifications from v2 to v3 (default)
- `--v2`: update only vendor namespace

See command help page:

```bash
docker run --rm -t ghcr.io/hustlahusky/spaceonfire2warp --help
```

After the tool has done its job, you should check that everything is OK:

- update composer dependencies: replace `spaceonfire/` vendor prefix with `getwarp/`, use a correct package version;
- review changes made by script: fix some missed errors, fix formatting, etc.

## License

The MIT License (MIT). Please see [license file](LICENSE.md) for more information.

[spaceonfire]: https://github.com/spaceonfire/spaceonfire
[getwarp]: https://github.com/getwarp/warp

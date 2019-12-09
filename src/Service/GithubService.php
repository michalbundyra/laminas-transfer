<?php

declare(strict_types=1);

namespace Laminas\Transfer\Service;

use Github\Client as GithubClient;
use Github\Exception\ExceptionInterface as GithubException;

use function preg_match;
use function sprintf;

class GithubService
{
    /** @var null|GithubClient */
    private $client;

    /** @var string */
    private $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
     * Create a repository on github.
     *
     * Creates the repository, and then returns the URL for it.
     *
     * @return string URL to the new repository
     * @throws GithubService\FailureCreatingRepositoryException
     */
    public function createRepository(
        string $org,
        string $repo,
        string $description = '',
        string $homepage = ''
    ) : string {
        try {
            $created = $this
                ->getClient()
                ->api('repo')
                ->create(
                    $repo,
                    $description,
                    $homepage,
                    $public = true,
                    $org,
                    $hasIssues = true,
                    $hasWiki = false,
                    $hasDownloads = true,
                    $teamId = null,
                    $autoInit = false,
                    $hasProjects = true
                );
        } catch (GithubException $e) {
            throw GithubService\FailureCreatingRepositoryException::forPackage($org, $repo, $e);
        }

        return $created['html_url'];
    }

    /**
     * Set repository topics/keywords
     *
     * @throws GithubService\FailureSettingRepositoryTopicsException
     */
    public function setRepositoryTopics(string $org, string $repo, array $topics) : void
    {
        try {
            $created = $this
                ->getClient()
                ->api('repo')
                ->replaceTopics($org, $repo, $topics);
        } catch (GithubException $e) {
            throw GithubService\FailureSettingRepositoryTopicsException::forPackage($org, $repo, $e);
        }
    }

    /**
     * @throws GithubService\FailureCreatingReleaseException
     */
    public function createRelease(string $org, string $repo, string $version, string $changelog) : void
    {
        $changelog = sprintf(
            "# %s/%s %s\n\n%s",
            $org,
            $repo,
            $version,
            $changelog
        );

        try {
            $this
                ->getClient()
                ->api('repo')
                ->releases()
                ->create($org, $repo, [
                    'tag_name' => $version,
                    'name' => $version,
                    'body' => $changelog,
                    'draft' => false,
                    'prerelease' => $this->isVersionPrerelease($version),
                ]);
        } catch (GithubException $e) {
            throw GithubService\FailureCreatingReleaseException::forPackageVersion($org, $repo, $version, $e);
        }
    }

    /**
     * @throws GithubService\FailureArchivingRepositoryException
     */
    public function archiveRepository(string $org, string $repo) : void
    {
        try {
            $this
                ->getClient()
                ->api('repo')
                ->update($org, $repo, [
                    'archive' => true,
                ]);
        } catch (GithubException $e) {
            throw GithubService\FailureArchivingRepositoryException::forPackage($org, $repo, $e);
        }
    }

    private function getClient() : GithubClient
    {
        if ($this->client) {
            return $this->client;
        }

        $this->client = new GithubClient();
        $this->client->authenticate($this->token, GithubClient::AUTH_HTTP_TOKEN);
    }

    private function isVersionPrerelease(string $version) : bool
    {
        if (preg_match('/(alpha|a|beta|b|rc|dev)\d+$/i', $version)) {
            return true;
        }
        return false;
    }
}

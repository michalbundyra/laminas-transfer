<?php

declare(strict_types=1);

namespace Laminas\Transfer\Service;

use Github\Client as GithubClient;
use Github\Exception\ExceptionInterface as GithubException;

use function sprintf;

class GithubService
{
    private const LABELS = [
        [
            'name' => 'Awaiting Author Updates',
            'color' => '#e11d21',
            'description' => '',
        ],
        [
            'name' => 'Awaiting Maintainer Response',
            'color' => '#fbca03',
            'description' => '',
        ],
        [
            'name' => 'BC Break',
            'color' => '#e11d21',
            'description' => '',
        ],
        [
            'name' => 'Bug',
            'color' => '#fc2929',
            'description' => 'Something is not working',
        ],
        [
            'name' => 'Documentation',
            'color' => '#207def',
            'description' => '',
        ],
        [
            'name' => 'Documentation needed',
            'color' => '#c7def8',
            'description' => '',
        ],
        [
            'name' => 'Duplicate',
            'color' => '#cccccc',
            'description' => 'This issue or pull request already exists',
        ],
        [
            'name' => 'Enhancement',
            'color' => '#84b6eb',
            'description' => '',
        ],
        [
            'name' => 'Feature Removal',
            'color' => '#eb6420',
            'description' => '',
        ],
        [
            'name' => 'Feature Request',
            'color' => '#0052cc',
            'description' => '',
        ],
        [
            'name' => 'Good First Issue',
            'color' => '#7057ff',
            'description' => 'Good for newcomers',
        ],
        [
            'name' => 'Help Wanted',
            'color' => '#159818',
            'description' => '',
        ],
        [
            'name' => 'Invalid',
            'color' => '#e6e6e6',
            'description' => 'This does not seem right',
        ],
        [
            'name' => 'Question',
            'color' => '#cc317c',
            'description' => 'Further information is requested',
        ],
        [
            'name' => 'Revert Needed',
            'color' => '#e11d21',
            'description' => '',
        ],
        [
            'name' => 'Review Needed',
            'color' => '#ff9500',
            'description' => '',
        ],
        [
            'name' => 'Unit Test Needed',
            'color' => '#eb6420',
            'description' => '',
        ],
        [
            'name' => 'Work In Progress',
            'color' => '#0b02e1',
            'description' => '',
        ],
        [
            'name' => 'Won\t Fix',
            'color' => '#ffffff',
            'description' => 'This will not be worked on',
        ],
    ];

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
     * Set issue labels for the repository
     *
     * @throws GithubService\FailureSettingRepositoryIssueLabelsException
     */
    public function createRepositoryIssueLabels(string $org, string $repo) : void
    {
        $api = $this->getClient()->api('issue')->labels();
        foreach (self::LABELS as $labelData) {
            try {
                $api->create($org, $repo, $labelData);
            } catch (GithubException $e) {
                throw GithubService\FailureSettingRepositoryIssueLabelsException::forPackage($org, $repo, $e);
            }
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
}

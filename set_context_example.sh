# Sets an example GITHUB_CONTEXT for local testing
# Edit pr_body_example.txt to set the PR's body
# run with source ./set_context_example.sh
export CI_PR_BODY="$(cat pr_body_example.txt)"
export CI_PACKAGE_BRANCH="circleci-project-setup"
export CI_PROJECT="docker-compose-actions-workflow"

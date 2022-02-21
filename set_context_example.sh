# Sets variables normally set by circleci but for local testing
# Edit pr_body_example.txt to set the PR's body
# run with source ./set_context_example.sh
export CI_PR_BODY="$(cat pr_body_example.txt)"
export CI_PACKAGE_BRANCH="updates-for-ci"
export CI_PROJECT="processmaker"
export IMAGE_TAG="processmaker-updates-for-ci"

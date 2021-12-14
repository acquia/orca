const core = require("@actions/core");
const exec = require("@actions/exec");
const utils = require("./utils");

async function run() {
  // taken from actions/checkout
  let githubWorkspacePath = process.env["GITHUB_WORKSPACE"];
  if (!githubWorkspacePath) {
    throw new Error("GITHUB_WORKSPACE not defined");
  }
  githubWorkspacePath = utils.resolvePath(githubWorkspacePath);

  const inputsRequired = [
    'sut_name',
    'sut_dir',
    'job',

  ]
  inputsRequired.forEach(input => {
    core.exportVariable(`ORCA_${input.toUpperCase()}`, core.getInput(input, {
      required: true,
    }));
  })

  const inputsNotRequired = [
    'sut_branch',
    'packages_config',
    'packages_config_alter',
    'enable_nightwatch',
    'self_test_coverage_clover',
    'live_test',
  ];
  inputsNotRequired.forEach(input => {
    core.exportVariable(`ORCA_${input.toUpperCase()}`, core.getInput(input));
  })

  // @todo figure out the self-test things.
  const scripts = [
    '../bin/ci/self-test/before_install.sh',
    '../bin/ci/before_install.sh',
    '../bin/ci/self-test/install.sh',
    '../bin/ci/install.sh',
    '../bin/ci/before_script.sh',
    '../bin/ci/self-test/script.sh',
    '../bin/ci/script.sh',
    '../bin/ci/self-test/after_success.sh',
    '../bin/ci/after_success.sh',
    '../bin/ci/after_failure.sh',
    '../bin/ci/after_script.sh',
  ];

  try {
    scripts.forEach(async script => {
      await exec.exec(['bash', utils.resolvePath(script)].join(' '));
    })
  } catch (error) {
    core.setFailed(error.message);
  }
}

(async () => {
  await run();
})().catch(error => {
  core.setFailed(error.message);
});

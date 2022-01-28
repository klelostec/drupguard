# Use a stock Docksal image as the base
ARG CLI_IMAGE
FROM $CLI_IMAGE

# Install the symfony CLI.
RUN set -xe; \
	echo "deb [trusted=yes] https://repo.symfony.com/apt/ /" | tee /etc/apt/sources.list.d/symfony-cli.list; \
    apt-get update >/dev/null; \
    apt-get -y --no-install-recommends install >/dev/null symfony-cli; \
    apt-get clean; rm -rf /var/lib/apt/lists/*

# All further commands will be performed as the docker user.
USER docker
SHELL ["/bin/bash", "-c"]

# Install additional global npm dependencies
RUN \
	# Initialize the user environment (this loads nvm)
	. $HOME/.profile && \
	# Install node packages
	npm install --global yarn

# IMPORTANT! Switching back to the root user as the last instruction.
USER root
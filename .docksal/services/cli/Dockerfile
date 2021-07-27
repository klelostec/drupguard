# Use a stock Docksal image as the base
ARG CLI_IMAGE
FROM $CLI_IMAGE

# Install the symfony CLI.
RUN \
   wget https://get.symfony.com/cli/installer -O - | bash && \
   mv /root/.symfony/bin/symfony /usr/local/bin/symfony

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
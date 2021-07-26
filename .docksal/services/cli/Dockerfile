# Use a stock Docksal image as the base
ARG CLI_IMAGE
FROM $CLI_IMAGE

# Install the symfony CLI.
RUN \
   wget https://get.symfony.com/cli/installer -O - | bash && \
   mv /root/.symfony/bin/symfony /usr/local/bin/symfony

# IMPORTANT! Switching back to the root user as the last instruction.
USER root
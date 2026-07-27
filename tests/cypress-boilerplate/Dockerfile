# Specify the image for your Docker container
FROM cypress/included:13.13.0

# Create working directory
ENV CYPRESS_WORK_DIR /opt/cypress
RUN mkdir -p ${CYPRESS_WORK_DIR}

# Sets the working directory inside the Docker container
WORKDIR ${CYPRESS_WORK_DIR}

# Copy Cypress tests to the working directory
COPY . .

# Install dependencies
RUN npm install

# Starts a bash shell in the container that ignores termination signals and keeps the container running indefinitely
ENTRYPOINT ["/bin/bash", "-c", "trap : TERM INT; sleep infinity & wait"]

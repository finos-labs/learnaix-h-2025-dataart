FROM python:3.12.7-slim
# Set working directory in container
WORKDIR /app
# Update system packages and install CA certificates
RUN apt-get update && apt-get install -y \
    ca-certificates \
    curl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*
# Update CA certificates to latest version
RUN update-ca-certificates
# Copy requirements file
COPY . ./
# Install Python dependencies
RUN pip install -r requirements.txt
# Create directory for Python files (mounted storage)
#RUN mkdir -p /app/python
EXPOSE 5000
## Set timezone to prevent clock skew issues
#ENV TZ=UTC
CMD ["python", "-m", "uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "5000"]

 
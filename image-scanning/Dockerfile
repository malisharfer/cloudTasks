# FROM python:latest
# WORKDIR /python-docker
# ADD https://netfree.link/dl/unix-ca.sh /home/netfree-unix-ca.sh 
# RUN cat  /home/netfree-unix-ca.sh | sh
# ENV NODE_EXTRA_CA_CERTS=/etc/ca-bundle.crt
# ENV REQUESTS_CA_BUNDLE=/etc/ca-bundle.crt
# ENV SSL_CERT_FILE=/etc/ca-bundle.crt
# COPY requirements.txt requirements.txt
# RUN pip install --trusted-host pypi.org --trusted-host pypi.python.org --trusted-host files.pythonhosted.org --root-user-action=ignore -r requirements.txt 
# RUN az login
# EXPOSE 8080 
# COPY . .
# CMD [ "python", "app.py"]

FROM python:latest
WORKDIR /python-docker
COPY requirements.txt requirements.txt
RUN pip install -r requirements.txt
RUN --mount=type=secret,id=AZURE_TENANT_ID \
    --mount=type=secret,id=AZURE_CLIENT_ID \
    --mount=type=secret,id=AZURE_CLIENT_SECRET \
    az login --service-principal -u $(cat /run/secrets/AZURE_CLIENT_ID) -p $(cat /run/secrets/AZURE_CLIENT_SECRET) --tenant $(cat /run/secrets/AZURE_TENANT_ID)
EXPOSE 8080 
COPY . .
CMD [ "python" , "app.py" ]
 
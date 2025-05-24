FROM python:3.12-alpine

RUN mkdir /opt/training

WORKDIR /opt/training

RUN apk add --no-cache nginx nodejs npm

COPY . .

RUN pip3 install -r requirements.txt

WORKDIR /opt/training/training/frontend

RUN npm ci
RUN npm run build

WORKDIR /opt/training
RUN python manage.py collectstatic --noinput

EXPOSE 80

RUN chmod +x ./init.sh

COPY config/default.conf /etc/nginx/http.d/default.conf

CMD ["/bin/sh", "-c", "./init.sh", "&&", "python", "manage.py", "collectstatic", "--noinput"]

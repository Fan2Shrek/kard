FROM python:3.10-slim AS base

FROM base AS main

COPY . /app
WORKDIR /app

RUN pip install -r requirements.txt

CMD ["python", "app.py"]

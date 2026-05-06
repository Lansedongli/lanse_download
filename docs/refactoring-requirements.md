# 帝国下载系统 V3.0 — 重构需求文档

> **文档版本**: v1.0  
> **生成日期**: 2026-05-06  
> **原系统**: 帝国下载系统 2.5（PHP 5.x + MySQL, 前后端不分离）  
> **目标系统**: 帝国下载系统 3.0（ThinkPHP 8.1 + Vue3 + MySQL 8.0 + Redis）

---

## 一、重构背景与动机

### 1.1 旧系统（V2.5）核心痛点

| 痛点 | 详情 |
|------|------|
| **PHP 版本落后** | 仅支持 PHP 5.x，无法运行在 PHP 7+ 环境 |
| **数据库驱动陈旧** | 不支持 MySQL PDO 模式，无法利用预处理语句防注入 |
| **前后端耦合** | PHP 模板混编 HTML/PHP/JS，维护困难，无法独立迭代 |
| **代码架构老旧** | 不符合现代化全栈设计规范，无 MVC 分层、无中间件、无 RESTful API |
| **安全风险** | 依赖 PHP 5.x 已停止安全更新，存在潜在漏洞 |

### 1.2 重构目标

1. **技术栈现代化**：PHP 8.1+、ThinkPHP 8.1、MySQL 8.0、Redis 7.x
2. **前后端分离**：后台 Vue3 + Element Plus，前台 Layui + jQuery，通过 RESTful API 通信
3. **功能完全对等**：保留 V2.5 全部核心功能，并在新架构上增强
4. **容器化部署**：Docker + Docker Compose 一键部署
5. **CI/CD 自动化**：GitHub Actions 流水线实现 lint → test → build → docker

---

## 二、新系统架构总览

### 2.1 技术栈

| 层级 | 技术选型 |
|------|----------|
| 后端框架 | ThinkPHP 8.1.x |
| PHP 版本 | 8.3 (CI 环境: 8.3) |
| 数据库 | MySQL 8.0+ |
| 缓存 | Redis 7.x (运行时) / File (测试降级) |
| 后台前端 | Vue 3 + Vite + Element Plus |
| 前台前端 | Layui + jQuery |
| 认证 | JWT (HMAC-SHA256) + Refresh Token |
| 权限 | RBAC (角色-权限 JSON 映射) |
| 容器化 | Docker + Docker Compose |
| CI/CD | GitHub Actions |

### 2.2 项目目录结构

```
edown/new/
├── backend/                    # ThinkPHP 8.1 后端
│   ├── app/
│   │   ├── admin/controller/   # 后台 API 控制器
│   │   ├── api/controller/     # 前台 API 控制器
│   │   ├── common/
│   │   │   ├── model/          # 数据模型
│   │   │   ├── service/        # 业务服务层
│   │   │   ├── middleware/     # 中间件 (Auth, RBAC, RateLimit)
│   │   │   ├── exception/      # 异常处理
│   │   │   └── helper/         # 助手函数
│   │   └── command/            # CLI 命令
│   ├── config/                 # 配置文件
│   ├── route/
│   │   ├── api.php             # 前台 API 路由
│   │   └── admin.php           # 后台 API 路由
│   ├── docker/mysql/           # 数据库 Schema + 种子数据
│   ├── tests/                  # PHPUnit 测试套件
│   └── public/                 # Web 根目录
├── admin-frontend/             # Vue3 后台管理端
├── user-frontend/              # Layui 用户前端
├── docker/                     # Docker 配置
├── docs/                       # 项目文档
├── docker-compose.yml          # 容器编排
├── Dockerfile                  # 应用镜像
└── .github/workflows/ci.yml    # CI/CD 流水线
```

---

## 三、功能对照（V2.5 → V3.0）

### 3.1 核心功能迁移状态

| V2.5 功能模块 | V3.0 实现方案 | 状态 |
|--------------|--------------|:----:|
| **会员系统**（会员组/点数/有效期） | `user` + `user_group` 表，RESTful API | ✅ |
| **充值系统**（点卡/网银/手动充值） | `recharge` + `point_cards` + 支付接口 | ✅ |
| **下载管理**（软件信息/附件/权限） | `software` + `attachments` + 中间件 | ✅ |
| **万能整合接口** | `integration` 模块 | ✅ |
| **RBAC 权限管理** | `role` + `permissions` JSON 字段 + AdminAuthMiddleware | ✅ |
| **备份恢复系统** | `db` 模块 | ✅ |
| **广告管理** | `ads` 模块 | ✅ |
| **无限级分类** | `categories` 表（parent_id 递归） | ✅ |
| **静态页面生成** | `static` 模块 | ✅ |
| **下载防盗链** | 下载验证码 + 附件目录隔离 | ✅ |
| **模板标签系统** | `template_vars` 模块 | ✅ |
| **插件**（投票/友链/公告） | 内置模块 | ✅ |
| **管理登录日志** | `user_login_log` 表 | ✅ |

### 3.2 V3.0 新增特性

| 特性 | 说明 |
|------|------|
| **JWT 认证** | 替代旧系统 COOKIE + 随机密码，支持无状态 API |
| **RESTful API** | 前后端分离，统一 JSON 响应格式 `{code, message, data}` |
| **中间件体系** | AdminAuthMiddleware (JWT+RBAC), RateLimitMiddleware (限流) |
| **Docker 部署** | 一键启动 MySQL + PHP + Redis + Nginx |
| **CI/CD** | GitHub Actions lint → test → frontend build → docker image |
| **自动化测试** | PHPUnit 32 个测试用例覆盖 Auth/Payment/Software/Security |
| **Security Headers** | X-Content-Type-Options, X-Frame-Options, CSP 等 (待 Nginx 配置) |
| **PHP 8.3 特性** | 枚举、命名参数、match 表达式、类型声明 |

---

## 四、数据库设计

### 4.1 数据表清单（38 张表）

| 模块 | 核心表 | 说明 |
|------|--------|------|
| 认证 | `admin_user`, `user`, `user_login_log` | 管理员/用户/登录日志 |
| 权限 | `role` | RBAC 角色（permissions JSON 存储） |
| 软件 | `software`, `attachments`, `software_type`, `software_language` | 下载信息+附件+分类 |
| 充值 | `recharge_package`, `point_card`, `recharge_order` | 套餐/点卡/订单 |
| 支付 | `pay_channel`, `pay_config` | 支付渠道配置 |
| 广告 | `ad`, `ad_position` | 广告素材+广告位 |
| 系统 | `system_config`, `template_var`, `static_page` | 配置/模板/静态页 |
| 整合 | `integration_config`, `integration_log` | 第三方系统整合 |
| 其他 | `category`, `friend_link`, `announcement`, `vote` 等 | 分类/友链/公告/投票 |

### 4.2 关键设计决策

- **表前缀**：无前缀（废弃旧系统的 `ed_` 前缀）
- **密码哈希**：bcrypt (`password_hash` / `password_verify`)，替代旧系统 MD5
- **权限存储**：`role.permissions` 使用 JSON 数组，支持 `["*"]` 超级管理员
- **软删除**：部分表使用 `delete_time` 字段
- **MySQL 8 严格模式**：所有 NOT NULL 字段必须有默认值或插入值

---

## 五、API 接口规范

### 5.1 统一响应格式

```json
{
  "code": 200,
  "message": "success",
  "data": {}
}
```

| HTTP 状态码 | 含义 |
|:-----------:|------|
| 200 | 成功 |
| 401 | 未认证/Token 无效 |
| 403 | 无权限 |
| 404 | 资源不存在 |
| 422 | 参数验证失败 |
| 429 | 请求过于频繁（限流） |
| 500 | 服务器内部错误 |

### 5.2 主要 API 端点

| 模块 | 方法 | 路径 | 认证 |
|------|------|------|:----:|
| 管理员登录 | POST | `/admin/api/auth/login` | — |
| 管理员信息 | GET | `/admin/api/auth/me` | Admin |
| 管理员退出 | POST | `/admin/api/auth/logout` | Admin |
| 角色管理 | CRUD | `/admin/api/roles` | Admin |
| 软件管理 | CRUD | `/admin/api/software` | Admin |
| 分类管理 | CRUD | `/admin/api/categories` | Admin |
| 支付渠道 | CRUD | `/admin/api/pay/channels` | Admin |
| 充值套餐 | CRUD | `/admin/api/pay/packages` | Admin |
| 用户管理 | CRUD | `/admin/api/users` | Admin |
| 用户注册 | POST | `/api/v1/auth/register` | — |
| 用户登录 | POST | `/api/v1/auth/login` | — |
| 用户信息 | GET | `/api/v1/user/me` | User |

---

## 六、测试规划

### 6.1 测试框架

- **PHPUnit 10.5.63** + 自定义 `ApiTestCase` 基类
- 测试数据库: `empiredown_test`，通过 `schema_full.sql` 初始化
- CI 中启动 PHP 内置服务器 `0.0.0.0:8080`，测试向此地址发 HTTP 请求

### 6.2 测试覆盖范围（32 用例）

| 测试套件 | 用例数 | 覆盖内容 |
|----------|:-----:|----------|
| `AuthTest` | 8 | 管理员登录、Token 验证、限流、RBAC 权限隔离 |
| `PaymentTest` | 10 | 支付渠道/套餐 CRUD、支付流程 |
| `SoftwareTest` | 8 | 软件/附件 CRUD、分类关联 |
| `SecurityTest` | 6 | SQL 注入、XSS、未授权访问、超长输入 |

### 6.3 当前测试状态

| 阶段 | 通过 | 失败 | 说明 |
|:----:|:---:|:---:|------|
| v1 | 5/32 | 27 | 数据库连接失败（配置变量名不匹配） |
| v2 | 5/32 | 27 | 密码哈希不匹配（种子数据 MD5 → bcrypt） |
| v3 | 7/32 | 25 | auth/me 通过，但 RBAC 权限检查拦截 |
| **当前** | **待验证** | — | 修复 JWT key + role_id=1 直接放行 |

---

## 七、部署方案

### 7.1 Docker Compose 部署

```yaml
services:
  mysql:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: empiredown
    volumes:
      - ./backend/docker/mysql/schema_full.sql:/docker-entrypoint-initdb.d/init.sql

  php:
    build: .
    ports:
      - "8080:80"
    depends_on:
      - mysql
      - redis

  redis:
    image: redis:7-alpine
```

### 7.2 CI/CD 流水线

```
代码推送 → lint (语法检查) → test (PHPUnit 32用例) → frontend (Vue3构建) → docker (镜像打包)
```

- 触发条件: `push` 到 `main`/`develop`，`PR` 到 `main`
- Docker 镜像仅在 `main` 分支构建
- 前端构建产物保留 7 天

---

## 八、安全基线

### 8.1 审计结果（2026-05-06）

| 风险等级 | 数量 | 说明 |
|:--------:|:---:|------|
| 🔴 高危 | 0 | — |
| 🟡 中危 | 3 | IDOR 统一返回 404、Security Headers、日志保护 |
| 🟢 低危 | 4 | CORS、文件大小限制、HSTS、CSP |

### 8.2 已实现安全措施

- ✅ bcrypt 密码哈希
- ✅ JWT HMAC-SHA256 + Token 黑名单
- ✅ 登录限流（10次/分钟）
- ✅ RBAC 权限隔离
- ✅ ThinkPHP ORM 参数化查询（防 SQL 注入）
- ✅ 视图自动 HTML 转义（防 XSS）
- ✅ 文件类型白名单校验

### 8.3 待完成安全项

- [ ] Nginx 添加 Security Headers (X-Content-Type-Options, X-Frame-Options, CSP)
- [ ] IDOR 统一返回 404（当前部分返回 200）
- [ ] 运行时日志文件限制 Web 直接访问
- [ ] 附件上传大小限制配置

---

## 九、当前进度与待办

### 9.1 已完成

| 任务 | 状态 |
|------|:--:|
| 后端 API 开发（全部模块） | ✅ |
| 数据库设计（38 张表 + 种子数据） | ✅ |
| JWT 认证 + RBAC 中间件 | ✅ |
| 后台前端基本框架 (Vue3 + Element Plus) | ✅ |
| 前台前端基本框架 (Layui) | ✅ |
| Docker 容器化配置 | ✅ |
| CI/CD 流水线 (lint/test/frontend/docker) | ✅ |
| PHPUnit 32 测试用例编写 | ✅ |
| 安全审计报告 | ✅ |
| Git 仓库 + GitHub Actions 集成 | ✅ |

### 9.2 进行中

| 任务 | 状态 |
|------|:--:|
| 测试修复（解决 RBAC 权限 403） | 🔄 |
| 32 测试全部通过 | 🔄 |

### 9.3 待办

| 任务 | 优先级 |
|------|:------:|
| 前端页面完整开发（管理端各模块详情页） | 高 |
| Nginx Security Headers 配置 | 中 |
| IDOR 统一 404 返回 | 中 |
| 日志文件访问限制 | 中 |
| CORS 配置（如需跨域） | 低 |
| HSTS 配置（上线后） | 低 |
| 性能测试 & 优化 | 低 |
| 生产环境部署文档 | 低 |

---

## 十、默认账号

| 角色 | 用户名 | 密码 | role_id |
|------|--------|------|:------:|
| 超级管理员 | `admin` | `admin123` | 1 |
| 测试用户 | `testuser` | `test123` | — |

---

> **下一步行动**: 等待 GitHub Actions 最新 CI 运行结果，确认 RBAC 修复后 32 个测试全部通过。

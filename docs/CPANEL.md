# cPanel 上传清单（FORMA AR Furniture）

## 1. 准备要上传的文件

上传整个项目文件夹，但 **不要上传** 这些（或上传后立刻改）：

| 不要上传 / 勿公开 | 原因 |
|------------------|------|
| `config/config.php` | 含本机密码、局域网 IP；在服务器上现建 |
| `.tools/` | 本机 PortableGit，无用 |
| `models/*.glb`（可选） | 太大可稍后在后台上传；空目录保留即可 |
| `.git/` | 不必上传 |

建议用 **ZIP 压缩** 后在 cPanel File Manager 解压。

## 2. 创建数据库

1. cPanel → **MySQL® Databases**
2. 创建数据库（例如 `用户名_ar_furniture`）
3. 创建用户并设密码，把用户 **加入数据库（ALL PRIVILEGES）**
4. **phpMyAdmin** → 选中该库 → 导入 `sql/database.sql`  
   - 若主机禁止 `CREATE DATABASE`，先删掉 SQL 文件开头的 `CREATE DATABASE` / `USE` 两行再导入

## 3. 配置 config.php

在服务器上复制：

```text
config/config.example.php  →  config/config.php
```

编辑 `config/config.php`：

```php
'base_url' => 'https://你的域名.com/你的文件夹',  // 重要：手机扫码 / AR 用
'public_url' => 'https://你的域名.com/你的文件夹',
'lan_ip' => '',   // 线上留空

'db' => [
    'host' => 'localhost',           // 多数 cPanel 如此；以主机说明为准
    'name' => '用户名_ar_furniture',
    'user' => '用户名_dbuser',
    'pass' => '你的数据库密码',
],
```

## 4. 权限

对以下目录设可写（通常 `755` 或 `775`）：

- `uploads/`
- `uploads/thumbs/`
- `models/`（若后台要上传 GLB）

## 5. HTTPS

cPanel → SSL/TLS 或 AutoSSL，打开 **https://你的域名/...**

## 6. 测试

1. 打开前台首页  
2. `/admin/login.php` → Auto fill → 登录（建议马上改密码）  
3. 打开带 GLB 的产品页，看 3D  
4. 手机用 **流量或 Wi‑Fi** 打开 **https 公网链接**，点 AR  

本地 XAMPP 的 `localhost` / `192.168...` 二维码在 cPanel 上不要再用；以 `base_url` 的 https 为准。

## 7. 管理员密码（可选）

上传后若仍是演示密码，可在服务器终端运行（若有 SSH）：

```bash
php tools/set_admin_password.php
```

或在 phpMyAdmin 里更新 `admins.password_hash`（用 PHP `password_hash` 生成）。

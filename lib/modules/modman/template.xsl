<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:import href="../admin/template.xsl" />

  <xsl:template match="content[@name='modman' and @mode='upgrade']" mode="content">
    <h2>Обновление модулей</h2>
    <xsl:choose>
      <xsl:when test="not(module)">
        <p>Похоже, что для используемых вами модулей обновлений нет.</p>
        <form method="post" action="admin/system/modules/reload?destination={$next}">
          <input type="submit" value="Обновить информацию" />
        </form>
      </xsl:when>
      <xsl:otherwise>
        <div class="modman">
          <p>Для вашей системы есть обновления. Ознакомиться со списком изменений можно кликнув в иконку <img src="lib/modules/modman/images/changelog.png" /> рядом с интересующим модулем.</p>
          <form method="post" action="admin/system/modules/upgrade?destination={$back}">
            <table>
              <tbody>
                <xsl:apply-templates select="module" />
              </tbody>
            </table>
            <p>
              <label><input type="checkbox" name="all" value="1" />Обновить все модули</label>
            </p>
            <input type="submit" value="Обновить выбранные" />
          </form>
        </div>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template match="content[@name='modman' and (@mode='install' or @mode='remove')]" mode="content">
    <h2>
      <xsl:value-of select="@title" />
    </h2>
    <xsl:choose>
      <xsl:when test="module">
        <xsl:apply-templates select="." mode="status" />
        <div class="modman">
          <xsl:apply-templates select="." mode="module-extras" />
          <form method="post" action="?q=modman.rpc&amp;action={@mode}&amp;destination={/page/request/@uri}">
            <table>
              <tbody>
                <xsl:apply-templates select="module">
                  <xsl:sort select="@id" />
                </xsl:apply-templates>
              </tbody>
            </table>
            <p class="note">Если при установке модулей возникают проблемы, вы можете скачать и установить их вручную, воспользовавшись специальной ссылкой.</p>
            <input class="form-submit" type="submit" value="Применить" />
          </form>
          <xsl:if test="not(module)">
            <p>Удивительно, но ни один модуль не найден.</p>
          </xsl:if>
        </div>
      </xsl:when>
      <xsl:when test="@count">
        <p>У вас установлены все возможные модули (<xsl:value-of select="@count" /> шт).</p>
      </xsl:when>
      <xsl:otherwise>
        <p>Информация о модулях отсутствует. Обычно она обновляется автоматически, <a href="http://code.google.com/p/molinos-cms/wiki/mod_cron">планировщиком задач</a>. Если этого не происходит, значит настройки сервера не позволяют устанавливать соединения с другими веб-серверами. В любом случае, у вас есть два варианта действий:</p>
        <ol>
          <li><a href="admin/system/modules/reload?destination={/page/@back}">Попытаться обновить список сейчас</a></li>
          <li><a href="http://code.google.com/p/molinos-cms/downloads/list?can=1&amp;q=label%3AR{@release}+type%3Amodule">Скачать и установить модули вручную</a></li>
        </ol>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template match="module">
    <tr>
      <xsl:attribute name="class">
        <xsl:if test="@installed">
          <xsl:text> installed</xsl:text>
        </xsl:if>
        <xsl:if test="not(@installed)">
          <xsl:text> uninstalled</xsl:text>
        </xsl:if>
        <xsl:text> section-</xsl:text>
        <xsl:value-of select="@section" />
      </xsl:attribute>

      <xsl:if test="../@mode='install' or ../@mode='remove' or ../@mode='upgrade'">
        <td>
          <input type="checkbox" name="modules[]" value="{@id}" id="check-{@id}">
            <xsl:if test="not(@url)">
              <xsl:attribute name="disabled">
                <xsl:text>disabled</xsl:text>
              </xsl:attribute>
            </xsl:if>
          </input>
        </td>
      </xsl:if>

      <xsl:if test="../@mode = 'settings'">
        <td>
          <a href="?q=admin.rpc&amp;action=form&amp;module=modman&amp;mode=config&amp;name={@id}&amp;cgroup=system&amp;destination={/page/request/@uri}">
            <img src="lib/modules/modman/configure.png" alt="settings" />
          </a>
        </td>
      </xsl:if>

      <td>
        <label for="check-{@id}">
          <xsl:value-of select="@id" />
        </label>
        <span class="description">
          <xsl:value-of select="@name" />
        </span>
      </td>

      <td class="nr">
        <xsl:if test="@docurl">
          <a href="{@docurl}" class="icon docurl">
            <span>
              <xsl:text>документация</xsl:text>
            </span>
          </a>
        </xsl:if>
      </td>

      <td class="nl nr">
        <xsl:if test="@changelog">
          <a href="{@changelog}" class="icon changelog">
            <span>
              <xsl:text>изменения</xsl:text>
            </span>
          </a>
        </xsl:if>
      </td>

      <td class="nl">
        <xsl:if test="@url">
          <a href="{@url}" class="icon download">
            <span>
              <xsl:value-of select="@filename" />
            </span>
          </a>
        </xsl:if>
      </td>

      <!-- Номер устанвленной версии, если модуль не установлен — номер доступной. -->
      <td class="version">
        <xsl:if test="@installed">
          <xsl:choose>
            <xsl:when test="@version.local != @version">
              <del title="Есть обновление">
                <xsl:text>v</xsl:text>
                <xsl:value-of select="@version.local" />
              </del>
              <br/>
              <xsl:text>v</xsl:text>
              <xsl:value-of select="@version" />
            </xsl:when>
            <xsl:otherwise>
              <xsl:text>v</xsl:text>
              <xsl:value-of select="@version.local" />
            </xsl:otherwise>
          </xsl:choose>
        </xsl:if>
        <xsl:if test="not(@installed)">
          <xsl:text>v</xsl:text>
          <xsl:value-of select="@version" />
        </xsl:if>
      </td>
    </tr>
  </xsl:template>

  <!-- Дополнительные элементы для фильтрации модулей. -->
  <xsl:template match="modules[@mode = 'addremove']" mode="module-extras">
    <label class="filter">
      <span>Показать:</span>
      <select>
        <option value="">все модули</option>
        <option value="installed">только установленные</option>
        <option value="uninstalled">не установленные</option>
        <option value="section-base">Основная функциональность</option>
        <option value="section-admin">Администрирование</option>
        <option value="section-core">Ядро</option>
        <option value="section-blog">Блоги</option>
        <option value="section-spam">Спам</option>
        <option value="section-commerce">Коммерция</option>
        <option value="section-interaction">Интерактив</option>
        <option value="section-performance">Производительность</option>
        <option value="section-service">Служебные</option>
        <option value="section-multimedia">Мультимедиа</option>
        <option value="section-syndication">Обмен данными</option>
        <option value="section-templating">Шаблоны</option>
        <xsl:if test="count(module[@section = 'visual'])">
          <option value="section-visual">Визуальные редакторы</option>
        </xsl:if>
        <xsl:if test="count(module[@section = 'custom'])">
          <option value="section-custom">Локальные</option>
        </xsl:if>
      </select>
    </label>
  </xsl:template>

  <xsl:template match="content" mode="status">
    <xsl:if test="status">
      <div id="modmanresult">
        <p>Результат выполнения операции:</p>
        <ol>
          <xsl:for-each select="status">
            <li>
              <xsl:value-of select="@module" />
              <xsl:text>: </xsl:text>
              <xsl:choose>
                <xsl:when test="@result = 'removed'">
                  <xsl:text>удалён</xsl:text>
                </xsl:when>
                <xsl:when test="@result = 'installed'">
                  <xsl:text>установлен</xsl:text>
                </xsl:when>
                <xsl:otherwise>
                  <xsl:text>возникла ошибка</xsl:text>
                </xsl:otherwise>
              </xsl:choose>
            </li>
          </xsl:for-each>
        </ol>
      </div>
    </xsl:if>
  </xsl:template>
</xsl:stylesheet>

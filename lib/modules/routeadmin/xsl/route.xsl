<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:import href="../../admin/template.xsl" />

  <!-- таблица маршрутизации -->
  <xsl:template match="content[@name='routes']" mode="content">
    <div id="routelist">
      <h2>Маршруты</h2>
      <xsl:choose>
        <xsl:when test="not(route)">
          <p>Маршрутов пока нет, <a href="admin/structure/routes/add">добавить</a>?</p>
        </xsl:when>
        <xsl:otherwise>
          <form method="get" action="admin/structure/routes/delete">
            <table>
              <thead>
                <tr>
                  <th />
                  <th />
                  <th>Путь</th>
                  <th>Заголовок</th>
                  <th>Шаблоны</th>
                </tr>
              </thead>
              <tbody>
                <xsl:for-each select="route">
                  <tr>
                    <td>
                      <input type="checkbox" name="check[]" value="{@id}" />
                    </td>
                    <td class="icon">
                      <xsl:if test="@url">
                        <a href="{@url}" class="icon-locate">
                          <span>?</span>
                        </a>
                      </xsl:if>
                    </td>
                    <td class="path">
                      <a href="admin/structure/routes/edit?id={@id}">
                        <xsl:value-of select="@name" />
                      </a>
                    </td>
                    <td class="title">
                      <xsl:value-of select="@title" />
                    </td>
                    <td class="tt">
                      <xsl:if test="@theme">
                        <xsl:value-of select="/page/@sitefolder" />
                        <xsl:text>/themes/</xsl:text>
                        <xsl:value-of select="@theme" />
                      </xsl:if>
                    </td>
                  </tr>
                </xsl:for-each>
              </tbody>
            </table>
            <p><input type="submit" value="Удалить отмеченные" /> или <a  href="admin/structure/routes/add">добавить новый путь</a>.</p>
          </form>
        </xsl:otherwise>
      </xsl:choose>
    </div>
  </xsl:template>

  <xsl:template match="content[@name='route-delete']" mode="content">
    <div id="routelist">
      <h2>Удаление маршрутов</h2>
      <p>Следующие маршруты более не нужны, и их следует удалить:</p>
      <ol>
        <xsl:for-each select="page">
          <li class="tt">
            <xsl:value-of select="text()" />
          </li>
        </xsl:for-each>
      </ol>

      <form method="post" action="admin/structure/routes/delete">
        <xsl:for-each select="page">
          <input type="hidden" name="delete[]" value="{text()}" />
        </xsl:for-each>
        <label>
          <input type="checkbox" name="confirm" value="1" />
          <span>Подтверждаю</span>
        </label>
        <p><input type="submit" value="Удалить" /></p>
      </form>
    </div>
  </xsl:template>
</xsl:stylesheet>
